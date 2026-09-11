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
use Contao\CoreBundle\DataContainer\PaletteManipulator;

/**
 * Registration module.
 */
foreach (['registration', 'registrationNotificationCenter'] as $palette) {
    if (isset($GLOBALS['TL_DCA']['tl_module']['palettes'][$palette])) {
        PaletteManipulator::create()
            ->addLegend('rim_legend', 'email_legend', PaletteManipulator::POSITION_BEFORE, true)
            ->addField(['rim_active', 'rim_act_active'], 'rim_legend', PaletteManipulator::POSITION_APPEND)
            ->applyToPalette($palette, 'tl_module');
    }
}

// Register the sub palettes, don't forget the palettes ;).
$GLOBALS['TL_DCA']['tl_module']['palettes']['__selector__'][] = 'rim_active';
$GLOBALS['TL_DCA']['tl_module']['palettes']['__selector__'][] = 'rim_act_active';

$GLOBALS['TL_DCA']['tl_module']['subpalettes']['rim_active']     = 'rim_mailtemplate,rim_do_syslog';
$GLOBALS['TL_DCA']['tl_module']['subpalettes']['rim_act_active'] = 'rim_act_mailtemplate,rim_act_do_syslog';

/**
 * Data change module.
 */
foreach (['personalData', 'lostPassword', 'lostPasswordNotificationCenter'] as $palette) {
    if (!isset($GLOBALS['TL_DCA']['tl_module']['palettes'][$palette])) {
        continue;
    }
    $parts = StringUtil::trimsplit(';', $GLOBALS['TL_DCA']['tl_module']['palettes'][$palette]);
    foreach ($parts as $key => $part) {
        if (str_contains($part, '{template_legend')) {
            ArrayUtil::arrayInsert($parts, $key, ['{rim_legend:hide},rim_change_active']);
            break;
        }
    }
    $GLOBALS['TL_DCA']['tl_module']['palettes'][$palette] = implode(';', $parts);
}

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
