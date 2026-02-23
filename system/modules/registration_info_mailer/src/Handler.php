<?php

/**
 * Contao Open Source CMS
 *
 * @copyright  MEN AT WORK 2016
 * @package    registration_info_mailer
 * @license    GNU/LGPL
 * @filesource
 */

namespace RegistrationInfoMailer;

use NotificationCenter\Model\Notification;

/**
 * Class registration_info_mailer
 *
 * @package RegistrationInfoMailer
 */
class Handler
{
    /**
     * Must be static because the loadLanguageFile hook call the method
     * without an instance. so we need a static property here ...
     *
     * @var array
     */
    protected static $arrUserOptions = array();

    /**
     * Send an email if a new user registers on the contao installation.
     *
     * @param int     $intId     The id of the new member.
     *
     * @param array   $arrData   The data of the new member.
     *
     * @param \Module $objModule The module and his current settings.
     *
     * @return void
     */
    public function sendRegistrationMail($intId, $arrData, $objModule)
    {
        // Check if the registration mail should be send.
        if ($objModule->rim_active == 1) {
            // Store the registration data.
            self::$arrUserOptions       = $arrData;
            self::$arrUserOptions['id'] = $intId;

            // Check if we have all needed data.
            if (!strlen($objModule->rim_mailtemplate)) {
                \Controller::log(
                    'RIM: failed to send the registration mail. The module needs more email information. Please check the module configuration.',
                    __CLASS__ . '::' . __FUNCTION__,
                    'ERROR'
                );

                return;
            }

            /** @var Notification $objNotification */
            $objNotification = Notification::findByPk($objModule->rim_mailtemplate);
            if (null !== $objNotification) {
                $arrTokens = $arrData;
                $objNotification->send($arrTokens, $arrData['language']); // Language is optional
            }

            // Log to tl_log if the user set the option.
            if ($objModule->rim_do_syslog == 1) {
                \Controller::log('RIM: a registration info mail has been send. Check your email.log for more information.',
                    __CLASS__ . '::' . __FUNCTION__,
                    'GENERAL'
                );
            }

            // done :) lets cleanup and get some food, maybe a big pizza or a nice tasty burger.
            unset($objMail);
        }
    }

    /**
     * Send an email if a user account is activated.
     *
     * @param \FrontendUser $objUser   The current user object.
     *
     * @param array         $formData  All current form data.
     *
     * @param \Module       $objModule The module and his current settings.
     *
     * @return void
     */
    public function sendChangeMail($objUser, $formData, $objModule = null)
    {
        // Check if the registration mail should be send.
        if (null !== $objModule && $objModule->rim_change_active == 1) {
            $this->getUserOptions($objUser);

            // Check if we have all needed data.
            if (!strlen($objModule->rim_change_mailtemplate)) {
                \Controller::log(
                    'RIM: failed to send the change mail. The module needs more email information. Please check the module configuration.',
                    __CLASS__ . '::' . __FUNCTION__,
                    'ERROR'
                );

                return;
            }

            /** @var Notification $objNotification */
            $objNotification = Notification::findByPk($objModule->rim_change_mailtemplate);
            if (null !== $objNotification) {
                $arrTokens = self::$arrUserOptions;
                $objNotification->send($arrTokens, $objUser->language); // Language is optional
            }

            // Log to tl_log if the user set the option.
            if ($objModule->rim_change_do_syslog == 1) {
                \Controller::log(
                    'RIM: An change info mail for the user ' . $objUser->id . ' has been send.',
                    __CLASS__ . '::' . __FUNCTION__,
                    'GENERAL'
                );
            }

            unset($objMail);
        }
    }


    /**
     * Send an email if a user account is activated.
     *
     * @param \FrontendUser $objUser   The current user object.
     *
     * @param \Module       $objModule The module and his current settings.
     *
     * @return void
     */
    public function sendActivationMail($objUser, $objModule)
    {
        // Check if the registration mail should be send.
        if ($objModule->rim_act_active == 1) {
            $this->getUserOptions($objUser);

            // Check if we have all needed data.
            if (!strlen($objModule->rim_act_mailtemplate)) {
                \Controller::log(
                    'RIM: failed to send the activation mail. The module needs more email informations. Please check the module configuration.',
                    __CLASS__ . '::' . __FUNCTION__,
                    'ERROR'
                );

                return;
            }

            /** @var Notification $objNotification */
            $objNotification = Notification::findByPk($objModule->rim_act_mailtemplate);
            if (null !== $objNotification) {
                $arrTokens = self::$arrUserOptions;
                $objNotification->send($arrTokens, $objUser->language); // Language is optional
            }

            // Log to tl_log if the user set the option.
            if ($objModule->rim_act_do_syslog == 1) {
                \Controller::log(
                    'RIM: An activation info mail for the user ' . $objUser->id . ' has been send.',
                    __CLASS__ . '::' . __FUNCTION__,
                    'GENERAL'
                );
            }

            unset($objMail);
        }
    }


    /**
     * Replace the insert tags {{rim::%useroption%}} with the $arrData from the registration
     *
     * @param string $strTag
     *
     * @return mixed
     */
    public function replaceRimInsertTags($strTag)
    {
        $arrTemp = explode('::', $strTag);

        if ('rim' !== $arrTemp[0]) {
            return false;
        }

        if (empty($arrTemp[1])) {
            return '';
        }

        return self::$arrUserOptions[$arrTemp[1]] ?? '';
    }

    /**
     * Send the mail.
     */
    public function sendMemberMail($dc)
    {
        $objUser = \Database::getInstance()
            ->prepare('SELECT * FROM tl_member WHERE id = ?')
            ->execute($dc->activeRecord->id);

        // Send the mail is checkbox is set.
        if ($objUser->rim_send_mail) {
            $this->getUserOptions($objUser);

            /** @var Notification $objNotification */
            $intTemplateId   = ($objUser->login) ? $objUser->rim_activate_mailtemplate : $objUser->rim_deactivate_mailtemplate;
            $objNotification = Notification::findByPk($intTemplateId);
            if (null !== $objNotification) {
                $arrTokens = self::$arrUserOptions;
                $objNotification->send($arrTokens, $objUser->language); // Language is optional
            }

        }
    }

    /**
     * generate the "Help Wizard" with the values from tl_member
     *
     * @param string $strContent
     * @param string $strTemplate
     */
    public function generateHelpWizard($strContent, $strTemplate)
    {
        if ($strContent == 'explain') {
            \Controller::loadLanguageFile('tl_member');
            $arrFields      = \Database::getInstance()->listFields('tl_member');
            $arrFieldsClean = array();
            $arrRemoveTags  = array(
                'id',
                'tstamp',
                'password',
                'locked',
                'session',
                'dateAdded',
                'currentLogin',
                'lastLogin',
                'activation',
                'autologin',
                'createdOn'
            );

            // Clean the array, not necessary but maybe i add a filter later
            // edit: i told you i add a filter...and here he is ;)
            foreach ($arrFields as $value) {
                if (!in_array($value['name'], $arrRemoveTags)) {
                    $arrFieldsClean[] = $value['name'];
                }
            }

            foreach ($arrFieldsClean as $v) {
                $GLOBALS['TL_LANG']['XPL']['rim_helper'][] = array
                (
                    '{{rim::' . $v . '}}',
                    '<strong>' . $GLOBALS['TL_LANG']['tl_member'][$v][0] . '</strong> - ' . $GLOBALS['TL_LANG']['tl_member'][$v][1]
                );
            }
        }
    }

    protected function getUserOptions($objUser)
    {
        // Force all user options we have to the rim insert tags.
        foreach (\Database::getInstance()->getFieldNames('tl_member') as $v) {
            // Don't use empty here.
            if ($objUser->$v != '') {
                self::$arrUserOptions[$v] = $objUser->$v;
            }
        }
    }
}
