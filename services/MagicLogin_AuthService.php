<?php

/**
 * MagicLogin_AuthService
 *
 * Author: Aaron Berkowitz <aaron.berkowitz@me.com>
 *
 */

namespace Craft;

use RandomLib;
use SecurityLib;

class MagicLogin_AuthService extends BaseApplicationComponent
{
    public function createMagicLogin($email, $redirectUrl)
    {
        // Look up user
        $user = craft()->users->getUserByEmail($email);

        if ($user === null || $user->status != 'active') {
            return false;
        }

        // For security purposes, we need to expire old login links for this user
        $this->expireUserLinks($user);

        // And so the database doesn't get flooded, we'll garbagecollect old login links as well
        $this->garbageCollectLinks();

        // Create random tokens
        $factory = new RandomLib\Factory();
        $generator = $factory->getMediumStrengthGenerator();
        $publicKey = $generator->generateString(64, 'abcdefghjkmnpqrstuvwxyz23456789');
        $privateKey = $generator->generateString(128, 'abcdefghjkmnpqrstuvwxyz23456789');
        $timestamp = time();

        // Populate Record
        $record = new MagicLogin_AuthRecord();
        $record->userId = $user->id;
        $record->publicKey = $publicKey;
        $record->privateKey = $privateKey;
        $record->timestamp = $timestamp;
        $record->redirectUrl = $redirectUrl;
        $record->save();

        $signature = $this->generateSignature($privateKey, $publicKey, $timestamp);

        $settings = craft()->plugins->getPlugin('magiclogin')->getSettings();

        $magicLogin = craft()->getSiteUrl(). $settings->authUri ."/$publicKey/$timestamp/$signature";

        return $magicLogin;
    }

    public function generateSignature($privateKey, $publicKey, $timestamp)
    {
        $stringToHash = implode('-', array($publicKey, $timestamp));

        $signature = hash_hmac('sha1', $stringToHash, $privateKey);

        return $signature;
    }

    public function getAuthorization($publicKey)
    {
        $record = new MagicLogin_AuthRecord();

        $authRecord = $record->findByAttributes(array('publicKey'=>$publicKey));

        return $authRecord;
    }

    public function sendEmail($emailAddress, $link, $user)
    {
        // load plugin settings
        $settings = craft()->plugins->getPlugin('magiclogin')->getSettings();

        // setup an email model and load up the info we have already
        $email = new EmailModel();   
        $email->toEmail = $emailAddress;
        $email->subject = $settings->emailSubject;

        // now we're going to fire up the template parser,
        // passing the link expiration time and the link to the email template

        // first the plaintext
        $email->body = craft()->templates->render($settings->emailTemplatePlain, array(
            'linkExpirationTime' => $settings->linkExpirationTime . ' minutes',
            'link' => $link,
            'user' => $user
        ));

        // first the plaintext
        if( !empty($settings->emailTemplateHtml) )
        {
            $email->htmlBody = craft()->templates->render($settings->emailTemplateHtml, array(
                'linkExpirationTime' => $settings->linkExpirationTime . ' minutes',
                'link' => $link,
                'user' => $user
            ));
        }
        

        try {
            $success = craft()->email->sendEmail($email);

            return $success;
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }

    public function expireUserLinks($user)
    {
        // Delete all existing authorization records with this user id
        craft()->db->createCommand()
            ->delete(
                'magiclogin_authorizations',
                'userId=:userId', 
                array(':userId' => $user->id)
            );

        return true;    

    }

    public function garbageCollectLinks()
    {
        // load plugin settings
        $settings = craft()->plugins->getPlugin('magiclogin')->getSettings();

        // using the expiration time specified in the settings,
        // calculate the timestamp which we should delete authorizations
        // that were created before
        $deleteBefore =  time() - ($settings->linkExpirationTime * 60);

        craft()->db->createCommand()
            ->delete(
                'magiclogin_authorizations',
                'timestamp<:deleteBefore', 
                array(':deleteBefore' => $deleteBefore)
            );

        return true;    

    }
}
