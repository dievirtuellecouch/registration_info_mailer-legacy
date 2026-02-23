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

/**
 * Class RimHelper
 *
 * @package RegistrationInfoMailer
 */
class Helper
{
    /**
     * Clean the input, remove empty entries, trim the values, remove doublicates.
     *
     * @param mixed         $varValue
     *
     * @param DataContainer $dc
     *
     * @return string
     */
    public function save_sorter($varValue, DataContainer $dc)
    {
        // array_diff remove empty strings
        $arrBuffer = array_diff(array_unique(array_map('trim', explode(',', $varValue))), array(''));
        sort($arrBuffer);
        $strBuffer = implode(',', $arrBuffer);

        return $strBuffer;
    }

    /**
     * format the result to a better readable version
     *
     * @param mixed         $varValue
     *
     * @param DataContainer $dc
     *
     * return string
     */
    public function load_sorter($varValue, DataContainer $dc)
    {
        return str_replace(',', ', ', $varValue);
    }

    /**
     * Load the default value from the settings, if no value is set
     *
     * @param type $strValue
     *
     * @param type $dc
     *
     * @return string
     */
    public function setDefaultTemplate($strValue, $dc)
    {
        if ($strValue == 0 && $GLOBALS['TL_CONFIG'][$dc->field . '_default']) {
            return $GLOBALS['TL_CONFIG'][$dc->field . '_default'];
        }

        return $strValue;
    }

    /**
     * get all available mail tamplates
     */
    public function getMailTemplates($strType)
    {
        $arrReturn    = array();
        $objTemplates = \Database::getInstance()
            ->prepare('SELECT * FROM tl_nc_notification WHERE type = ? ORDER BY title ASC')
            ->execute($strType);

        while ($objTemplates->next()) {
            $arrReturn[$objTemplates->id] = $objTemplates->title;
        }

        return $arrReturn;
    }

    public function getMemberActivationTemplates($strType)
    {
        return $this->getMailTemplates('member_activation_mail');
    }

    public function getMemberRegistrationTemplates($strType)
    {
        return $this->getMailTemplates('member_registration_mail');
    }

    public function getMemberChangeTemplates($strType)
    {
        return $this->getMailTemplates('account_change_mail');
    }

    public function getAccountActivationTemplates($strType)
    {
        return $this->getMailTemplates('account_activation_mail');
    }

    public function getAccountDeactivationTemplates($strType)
    {
        return $this->getMailTemplates('account_deactivation_mail');
    }

    /**
     * helper function to uncheck the checkbox
     */
    public function resetSendCheckbox($strValue)
    {
        $strValue = 0;

        return $strValue;
    }
}
