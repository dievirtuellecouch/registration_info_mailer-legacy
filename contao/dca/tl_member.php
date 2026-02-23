<?php

/**
 * Contao Open Source CMS
 *
 * @copyright  MEN AT WORK 2016
 * @package    registration_info_mailer
 * @license    GNU/LGPL
 * @filesource
 */

// Add the new fields to the palette.
$GLOBALS['TL_DCA']['tl_member']['palettes']['default'] = str_replace(
    'login;',
    'rim_send_mail,rim_activate_mailtemplate,rim_deactivate_mailtemplate,login;',
    $GLOBALS['TL_DCA']['tl_member']['palettes']['default']
);

/**
 * Fields
 */
$loginTlClass = (string) ($GLOBALS['TL_DCA']['tl_member']['fields']['login']['eval']['tl_class'] ?? '');
$GLOBALS['TL_DCA']['tl_member']['fields']['login']['eval']['tl_class'] = trim($loginTlClass.' clr');

$GLOBALS['TL_DCA']['tl_member']['fields']['rim_send_mail'] = array
(
    'label'     => &$GLOBALS['TL_LANG']['tl_member']['rim_send_mail'],
    'exclude'   => true,
    'inputType' => 'checkbox',
    'default'   => '1',
    'sql'       => "char(1) NOT NULL default '1'"
);

$GLOBALS['TL_DCA']['tl_member']['fields']['rim_activate_mailtemplate'] = array
(
    'label'     => &$GLOBALS['TL_LANG']['tl_member']['rim_activate_mailtemplate'],
    'exclude'   => true,
    'inputType' => 'select',
    'eval'      => array('tl_class' => 'w50'),
    'sql'       => "int(10) unsigned NOT NULL default '0'"
);

$GLOBALS['TL_DCA']['tl_member']['fields']['rim_deactivate_mailtemplate'] = array
(
    'label'     => &$GLOBALS['TL_LANG']['tl_member']['rim_deactivate_mailtemplate'],
    'exclude'   => true,
    'inputType' => 'select',
    'eval'      => array('tl_class' => 'w50'),
    'sql'       => "int(10) unsigned NOT NULL default '0'"
);
