<?php

/**
 * Contao Open Source CMS
 *
 * @copyright  MEN AT WORK 2014
 * @package    registration_info_mailer
 * @license    GNU/LGPL
 * @filesource
 */

use Contao\ArrayUtil;
use Contao\StringUtil;

/**
 * Registration module.
 */
$GLOBALS['TL_DCA']['tl_module']['palettes']['registration'] = str_replace(
    '{email_legend:hide}',
    '{rim_legend:hide},rim_active,rim_act_active;{email_legend:hide}',
    $GLOBALS['TL_DCA']['tl_module']['palettes']['registration']
);

// Register the sub palettes, don't forget the palettes ;).
$GLOBALS['TL_DCA']['tl_module']['palettes']['__selector__'][] = 'rim_active';
$GLOBALS['TL_DCA']['tl_module']['palettes']['__selector__'][] = 'rim_act_active';

$GLOBALS['TL_DCA']['tl_module']['subpalettes']['rim_active']     = 'rim_mailtemplate,rim_do_syslog';
$GLOBALS['TL_DCA']['tl_module']['subpalettes']['rim_act_active'] = 'rim_act_mailtemplate,rim_act_do_syslog';

/**
 * Data change module.
 */
$parts = StringUtil::trimsplit(';', $GLOBALS['TL_DCA']['tl_module']['palettes']['personalData']);
foreach ($parts as $key => $part) {
    if (stripos($part, '{template_legend') !== false) {
        ArrayUtil::arrayInsert($parts, $key - 1, array('{rim_legend:hide},rim_change_active'));
        break;
    }
}
$GLOBALS['TL_DCA']['tl_module']['palettes']['personalData'] = implode(';', $parts);

$parts = StringUtil::trimsplit(';', $GLOBALS['TL_DCA']['tl_module']['palettes']['lostPassword']);
foreach ($parts as $key => $part) {
    if (stripos($part, '{template_legend') !== false) {
        ArrayUtil::arrayInsert($parts, $key - 1, array('{rim_legend:hide},rim_change_active'));
        break;
    }
}
$GLOBALS['TL_DCA']['tl_module']['palettes']['lostPassword'] = implode(';', $parts);

// Register the sub palettes, don't forget the palettes ;).
$GLOBALS['TL_DCA']['tl_module']['palettes']['__selector__'][]       = 'rim_change_active';
$GLOBALS['TL_DCA']['tl_module']['subpalettes']['rim_change_active'] = 'rim_change_mailtemplate,rim_change_do_syslog';

/**
 * Add all global rim_ fields
 */
$GLOBALS['TL_DCA']['tl_module']['fields']['rim_active'] = array
(
    'label'     => &$GLOBALS['TL_LANG']['tl_module']['rim_active'],
    'exclude'   => true,
    'inputType' => 'checkbox',
    'eval'      => array('submitOnChange' => true, 'tl_class' => 'clr'),
    'sql'       => "char(1) NOT NULL default ''"
);

$GLOBALS['TL_DCA']['tl_module']['fields']['rim_act_active'] = array
(
    'label'     => &$GLOBALS['TL_LANG']['tl_module']['rim_act_active'],
    'exclude'   => true,
    'inputType' => 'checkbox',
    'eval'      => array('submitOnChange' => true, 'tl_class' => 'clr'),
    'sql'       => "char(1) NOT NULL default ''"
);

$GLOBALS['TL_DCA']['tl_module']['fields']['rim_change_active'] = array
(
    'label'     => &$GLOBALS['TL_LANG']['tl_module']['rim_change_active'],
    'exclude'   => true,
    'inputType' => 'checkbox',
    'eval'      => array('submitOnChange' => true, 'tl_class' => 'clr'),
    'sql'       => "char(1) NOT NULL default ''"
);

/**
 * Add all fields for the registration notification
 */
$GLOBALS['TL_DCA']['tl_module']['fields']['rim_mailtemplate'] = array
(
    'label'       => &$GLOBALS['TL_LANG']['tl_module']['rim_mailtemplate'],
    'exclude'     => true,
    'inputType'   => 'select',
    'explanation' => 'RimHelper',
    'eval'        => array('helpwizard' => true, 'tl_class' => 'w50'),
    'sql'         => "int(10) unsigned NOT NULL default '0'"
);

$GLOBALS['TL_DCA']['tl_module']['fields']['rim_do_syslog'] = array
(
    'label'     => &$GLOBALS['TL_LANG']['tl_module']['rim_do_syslog'],
    'exclude'   => true,
    'inputType' => 'checkbox',
    'eval'      => array('tl_class' => 'w50 m12'),
    'sql'       => "char(1) NOT NULL default ''"
);

/**
 * Add all fields for the change notification
 */
$GLOBALS['TL_DCA']['tl_module']['fields']['rim_change_mailtemplate'] = array
(
    'label'       => &$GLOBALS['TL_LANG']['tl_module']['rim_change_mailtemplate'],
    'exclude'     => true,
    'inputType'   => 'select',
    'explanation' => 'RimHelper',
    'eval'        => array('helpwizard' => true, 'tl_class' => 'w50'),
    'sql'         => "int(10) unsigned NOT NULL default '0'"
);

$GLOBALS['TL_DCA']['tl_module']['fields']['rim_change_do_syslog'] = array
(
    'label'     => &$GLOBALS['TL_LANG']['tl_module']['rim_change_do_syslog'],
    'exclude'   => true,
    'inputType' => 'checkbox',
    'eval'      => array('tl_class' => 'w50 m12'),
    'sql'       => "char(1) NOT NULL default ''"
);

/**
 * Add all fields for the activation notification
 */
$GLOBALS['TL_DCA']['tl_module']['fields']['rim_act_mailtemplate'] = array
(
    'label'       => &$GLOBALS['TL_LANG']['tl_module']['rim_act_mailtemplate'],
    'exclude'     => true,
    'inputType'   => 'select',
    'explanation' => 'RimHelper',
    'eval'        => array('helpwizard' => true, 'tl_class' => 'w50'),
    'sql'         => "int(10) unsigned NOT NULL default '0'"
);

$GLOBALS['TL_DCA']['tl_module']['fields']['rim_act_do_syslog'] = array
(
    'label'     => &$GLOBALS['TL_LANG']['tl_module']['rim_act_do_syslog'],
    'exclude'   => true,
    'inputType' => 'checkbox',
    'eval'      => array('tl_class' => 'w50 m12'),
    'sql'       => "char(1) NOT NULL default ''"
);
