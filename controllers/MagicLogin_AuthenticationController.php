<?php

/**
 * MagicLogin_AuthenticationController
 *
 * Author: Aaron Berkowitz <aaron.berkowitz@me.com>
 *
 */

namespace Craft;

class MagicLogin_AuthenticationController extends BaseController
{

    protected $allowAnonymous = true;

    public function actionAuthenticate()
    {

        $settings = craft()->plugins->getPlugin('magiclogin')->getSettings();

        $routeParams = craft()->urlManager->getRouteParams();

        $publicKey = $routeParams['variables']['publicKey'];

        $timestamp = $routeParams['variables']['timestamp'];

        $signature = $routeParams['variables']['signature'];

        $authenticationFailed = false;

        //Check for record with public key
        $authorizationRecord = craft()->magicLogin_auth->getAuthorization($publicKey);

        if ($authorizationRecord === null) {
            $authenticationFailed = true;
        }

        // Check if the signatures match
        $generatedSignature = craft()->magicLogin_auth->generateSignature(
            $authorizationRecord->privateKey,
            $publicKey,
            $timestamp
        );

        if ($signature != $generatedSignature) {
            $authenticationFailed = true;
        }

        // Check if timestamp is within bounds
        $timelimit = $authorizationRecord->timestamp + ($settings['linkExpirationTime'] * 60);

        if (time() > $timelimit) {
            $authenticationFailed = true;
        }

        // Check if one of our triggers from above has caused authentication to fail
        // and redirect to the path in the settings, so we can bail out of the process
        if($authenticationFailed)
        {
            $this->redirect($settings->linkErrorPath, true);
        }

        //If all this has been valid, login the user
        craft()->userSession->loginByUserId($authorizationRecord->userId);

        $redirectUrl = $authorizationRecord->redirectUrl;

        $authorizationRecord->delete();

        $this->redirect($redirectUrl);
    }

    public function actionLogin()
    {
        $this->requirePostRequest();

        $settings = craft()->plugins->getPlugin('magiclogin')->getSettings();

        $emailAddress = craft()->request->getPost('email');

        $redirectUrl = craft()->request->getPost('redirect') ?: $settings['redirectAfterLogin'];

        $link = craft()->magicLogin_auth->createMagicLogin($emailAddress, $redirectUrl);

        if ($link) {

            // Get the user model for the email address
            $user = craft()->users->getUserByEmail($emailAddress);

            $emailSent = craft()->magicLogin_auth->sendEmail($emailAddress, $link, $user);

            craft()->urlManager->setRouteVariables(array(
               'message' => 'Success! Check your email for your magic link.',
               'status' => 'success'
            ));
        } else {
            craft()->urlManager->setRouteVariables(array(
               'message' => 'Oops! Something went wrong. Please try your email address again.',
               'status' => 'fail'
            ));
        }
    }
}
