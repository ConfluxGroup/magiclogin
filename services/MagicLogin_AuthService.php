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

        // Create random tokens
        $factory = new RandomLib\Factory();
        
        $generator = $factory->getHighStrengthGenerator();

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
}
