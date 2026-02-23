<?php

/**
 * Contao Open Source CMS
 *
 * @copyright  MEN AT WORK 2014
 * @package    registration_info_mailer
 * @license    GNU/LGPL
 * @filesource
 */

/**
 * System configuration
 */

// Palettes Insert
$GLOBALS['TL_DCA']['tl_settings']['palettes']['default'] .= ';{rim_legend},rim_activate_mailtemplate_default,rim_deactivate_mailtemplate_default';

// Fields
$GLOBALS['TL_DCA']['tl_settings']['fields']['rim_activate_mailtemplate_default'] = array
(
    'label'            => &$GLOBALS['TL_LANG']['tl_settings']['rim_activate_mailtemplate_default'],
    'exclude'          => true,
    'inputType'        => 'select',
    'options_callback' => array('RegistrationInfoMailer\Helper', 'getAccountActivationTemplates'),
    'eval'             => array('tl_class' => 'w50')
);

$GLOBALS['TL_DCA']['tl_settings']['fields']['rim_deactivate_mailtemplate_default'] = array
(
    'label'            => &$GLOBALS['TL_LANG']['tl_settings']['rim_deactivate_mailtemplate_default'],
    'exclude'          => true,
    'inputType'        => 'select',
    'options_callback' => array('RegistrationInfoMailer\Helper', 'getAccountDeactivationTemplates'),
    'eval'             => array('tl_class' => 'w50')
);
