<?php

/**
 * MagicLogin Main File
 *
 * Author: Aaron Berkowitz <aaron.berkowitz@me.com>
 *
 */

namespace Craft;

class MagicLoginPlugin extends BasePlugin
{
    public function getName()
    {
        return Craft::t('Magic Login');
    }

    public function getVersion()
    {
        return '0.0.1';
    }

    public function getSchemaVersion()
    {
        return '0.0.1';
    }

    public function getDeveloper()
    {
        return 'Aaron Berkowitz';
    }

    public function getDeveloperUrl()
    {
        return 'https://github.com/aberkie/magiclogin';
    }

    public function getDescription()
    {
        return 'Simple password-less login for CraftCMS.';
    }

    public function registerSiteRoutes()
    {
        return array(
            $this->getSettings()['authUri'] . '/(?P<publicKey>\w+)/(?P<timestamp>\d+)/(?P<signature>\w+)' => array('action' => 'magicLogin/authentication/authenticate')
        );
    }

    protected function defineSettings()
    {
        return array(
            'linkExpirationTime' => array(AttributeType::Number, 'default' => 5),
            'redirectAfterLogin' => array(AttributeType::String, 'default' => '/admin'),
            'authUri' => array(AttributeType::String, 'default' => 'magiclogin/auth'),
            'emailSubject' => array(AttributeType::String, 'default' => craft()->getSiteName().' - Magic Login'),
            'emailTemplatePlain' => array(AttributeType::String, 'default' => 'magiclogin/_email'),
            'emailTemplateHtml' => array(AttributeType::String, 'default' => ''),
            'linkErrorPath' => array(AttributeType::String, 'default' => '')
        );
    }

    public function getSettingsHtml()
    {
        return craft()->templates->render(
            'magiclogin/settings',
            array(
                'settings' => $this->getSettings()
            )
        );
    }

    public function init()
    {
        require CRAFT_PLUGINS_PATH.'magiclogin/vendor/autoload.php';
    }
}
