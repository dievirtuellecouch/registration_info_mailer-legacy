<?php

declare(strict_types=1);

namespace MenAtWork\RegistrationInfoMailerBundle\EventListener\DataContainer;

use Contao\ArrayUtil;
use Contao\CoreBundle\DependencyInjection\Attribute\AsHook;
use Contao\StringUtil;

class DcaLoadListener
{
    #[AsHook('loadDataContainer')]
    public function onLoadDataContainer(string $table): void
    {
        if ('tl_module' === $table) {
            $this->extendTlModule();

            return;
        }

        if ('tl_member' === $table) {
            $this->extendTlMember();

            return;
        }

        if ('tl_settings' === $table) {
            $this->extendTlSettings();
        }
    }

    private function extendTlModule(): void
    {
        if (isset($GLOBALS['TL_DCA']['tl_module']['fields']['rim_active'])) {
            return;
        }

        $registrationPalette = (string) ($GLOBALS['TL_DCA']['tl_module']['palettes']['registration'] ?? '');

        if ('' !== $registrationPalette) {
            $GLOBALS['TL_DCA']['tl_module']['palettes']['registration'] = str_replace(
                '{email_legend:hide}',
                '{rim_legend:hide},rim_active,rim_act_active;{email_legend:hide}',
                $registrationPalette,
            );
        }

        $this->appendPaletteLegendBeforeTemplate('personalData', '{rim_legend:hide},rim_change_active');
        $this->appendPaletteLegendBeforeTemplate('lostPassword', '{rim_legend:hide},rim_change_active');

        $this->addSelector('tl_module', 'rim_active');
        $this->addSelector('tl_module', 'rim_act_active');
        $this->addSelector('tl_module', 'rim_change_active');

        $GLOBALS['TL_DCA']['tl_module']['subpalettes']['rim_active'] = 'rim_mailtemplate,rim_do_syslog';
        $GLOBALS['TL_DCA']['tl_module']['subpalettes']['rim_act_active'] = 'rim_act_mailtemplate,rim_act_do_syslog';
        $GLOBALS['TL_DCA']['tl_module']['subpalettes']['rim_change_active'] = 'rim_change_mailtemplate,rim_change_do_syslog';

        $GLOBALS['TL_DCA']['tl_module']['fields']['rim_active'] = [
            'label' => &$GLOBALS['TL_LANG']['tl_module']['rim_active'],
            'exclude' => true,
            'inputType' => 'checkbox',
            'eval' => ['submitOnChange' => true, 'tl_class' => 'clr'],
            'sql' => "char(1) NOT NULL default ''",
        ];

        $GLOBALS['TL_DCA']['tl_module']['fields']['rim_act_active'] = [
            'label' => &$GLOBALS['TL_LANG']['tl_module']['rim_act_active'],
            'exclude' => true,
            'inputType' => 'checkbox',
            'eval' => ['submitOnChange' => true, 'tl_class' => 'clr'],
            'sql' => "char(1) NOT NULL default ''",
        ];

        $GLOBALS['TL_DCA']['tl_module']['fields']['rim_change_active'] = [
            'label' => &$GLOBALS['TL_LANG']['tl_module']['rim_change_active'],
            'exclude' => true,
            'inputType' => 'checkbox',
            'eval' => ['submitOnChange' => true, 'tl_class' => 'clr'],
            'sql' => "char(1) NOT NULL default ''",
        ];

        $GLOBALS['TL_DCA']['tl_module']['fields']['rim_mailtemplate'] = [
            'label' => &$GLOBALS['TL_LANG']['tl_module']['rim_mailtemplate'],
            'exclude' => true,
            'inputType' => 'select',
            'explanation' => 'RimHelper',
            'eval' => ['helpwizard' => true, 'tl_class' => 'w50'],
            'sql' => "int(10) unsigned NOT NULL default '0'",
        ];

        $GLOBALS['TL_DCA']['tl_module']['fields']['rim_do_syslog'] = [
            'label' => &$GLOBALS['TL_LANG']['tl_module']['rim_do_syslog'],
            'exclude' => true,
            'inputType' => 'checkbox',
            'eval' => ['tl_class' => 'w50 m12'],
            'sql' => "char(1) NOT NULL default ''",
        ];

        $GLOBALS['TL_DCA']['tl_module']['fields']['rim_change_mailtemplate'] = [
            'label' => &$GLOBALS['TL_LANG']['tl_module']['rim_change_mailtemplate'],
            'exclude' => true,
            'inputType' => 'select',
            'explanation' => 'RimHelper',
            'eval' => ['helpwizard' => true, 'tl_class' => 'w50'],
            'sql' => "int(10) unsigned NOT NULL default '0'",
        ];

        $GLOBALS['TL_DCA']['tl_module']['fields']['rim_change_do_syslog'] = [
            'label' => &$GLOBALS['TL_LANG']['tl_module']['rim_change_do_syslog'],
            'exclude' => true,
            'inputType' => 'checkbox',
            'eval' => ['tl_class' => 'w50 m12'],
            'sql' => "char(1) NOT NULL default ''",
        ];

        $GLOBALS['TL_DCA']['tl_module']['fields']['rim_act_mailtemplate'] = [
            'label' => &$GLOBALS['TL_LANG']['tl_module']['rim_act_mailtemplate'],
            'exclude' => true,
            'inputType' => 'select',
            'explanation' => 'RimHelper',
            'eval' => ['helpwizard' => true, 'tl_class' => 'w50'],
            'sql' => "int(10) unsigned NOT NULL default '0'",
        ];

        $GLOBALS['TL_DCA']['tl_module']['fields']['rim_act_do_syslog'] = [
            'label' => &$GLOBALS['TL_LANG']['tl_module']['rim_act_do_syslog'],
            'exclude' => true,
            'inputType' => 'checkbox',
            'eval' => ['tl_class' => 'w50 m12'],
            'sql' => "char(1) NOT NULL default ''",
        ];
    }

    private function extendTlMember(): void
    {
        if (isset($GLOBALS['TL_DCA']['tl_member']['fields']['rim_send_mail']['sql'])) {
            return;
        }

        $defaultPalette = (string) ($GLOBALS['TL_DCA']['tl_member']['palettes']['default'] ?? '');

        if ('' !== $defaultPalette && !str_contains($defaultPalette, 'rim_send_mail')) {
            $GLOBALS['TL_DCA']['tl_member']['palettes']['default'] = str_replace(
                'login;',
                'rim_send_mail,rim_activate_mailtemplate,rim_deactivate_mailtemplate,login;',
                $defaultPalette,
            );
        }

        $loginTlClass = (string) ($GLOBALS['TL_DCA']['tl_member']['fields']['login']['eval']['tl_class'] ?? '');
        $GLOBALS['TL_DCA']['tl_member']['fields']['login']['eval']['tl_class'] = trim($loginTlClass.' clr');

        $GLOBALS['TL_DCA']['tl_member']['fields']['rim_send_mail'] = [
            'label' => &$GLOBALS['TL_LANG']['tl_member']['rim_send_mail'],
            'exclude' => true,
            'inputType' => 'checkbox',
            'default' => '1',
            'sql' => "char(1) NOT NULL default '1'",
        ];

        $GLOBALS['TL_DCA']['tl_member']['fields']['rim_activate_mailtemplate'] = [
            'label' => &$GLOBALS['TL_LANG']['tl_member']['rim_activate_mailtemplate'],
            'exclude' => true,
            'inputType' => 'select',
            'eval' => ['tl_class' => 'w50'],
            'sql' => "int(10) unsigned NOT NULL default '0'",
        ];

        $GLOBALS['TL_DCA']['tl_member']['fields']['rim_deactivate_mailtemplate'] = [
            'label' => &$GLOBALS['TL_LANG']['tl_member']['rim_deactivate_mailtemplate'],
            'exclude' => true,
            'inputType' => 'select',
            'eval' => ['tl_class' => 'w50'],
            'sql' => "int(10) unsigned NOT NULL default '0'",
        ];
    }

    private function extendTlSettings(): void
    {
        $defaultPalette = (string) ($GLOBALS['TL_DCA']['tl_settings']['palettes']['default'] ?? '');

        if ('' !== $defaultPalette && !str_contains($defaultPalette, 'rim_activate_mailtemplate_default')) {
            $GLOBALS['TL_DCA']['tl_settings']['palettes']['default'] .= ';{rim_legend},rim_activate_mailtemplate_default,rim_deactivate_mailtemplate_default';
        }

        $GLOBALS['TL_DCA']['tl_settings']['fields']['rim_activate_mailtemplate_default'] = [
            'label' => &$GLOBALS['TL_LANG']['tl_settings']['rim_activate_mailtemplate_default'],
            'exclude' => true,
            'inputType' => 'select',
            'eval' => ['tl_class' => 'w50'],
        ];

        $GLOBALS['TL_DCA']['tl_settings']['fields']['rim_deactivate_mailtemplate_default'] = [
            'label' => &$GLOBALS['TL_LANG']['tl_settings']['rim_deactivate_mailtemplate_default'],
            'exclude' => true,
            'inputType' => 'select',
            'eval' => ['tl_class' => 'w50'],
        ];
    }

    private function addSelector(string $table, string $field): void
    {
        if (!isset($GLOBALS['TL_DCA'][$table]['palettes']['__selector__'])) {
            $GLOBALS['TL_DCA'][$table]['palettes']['__selector__'] = [];
        }

        if (!\in_array($field, $GLOBALS['TL_DCA'][$table]['palettes']['__selector__'], true)) {
            $GLOBALS['TL_DCA'][$table]['palettes']['__selector__'][] = $field;
        }
    }

    private function appendPaletteLegendBeforeTemplate(string $paletteName, string $legend): void
    {
        $palette = (string) ($GLOBALS['TL_DCA']['tl_module']['palettes'][$paletteName] ?? '');

        if ('' === $palette || str_contains($palette, $legend)) {
            return;
        }

        $parts = StringUtil::trimsplit(';', $palette);

        foreach ($parts as $key => $part) {
            if (str_contains((string) $part, '{template_legend')) {
                ArrayUtil::arrayInsert($parts, max(0, $key - 1), [$legend]);
                $GLOBALS['TL_DCA']['tl_module']['palettes'][$paletteName] = implode(';', $parts);

                return;
            }
        }

        $parts[] = $legend;
        $GLOBALS['TL_DCA']['tl_module']['palettes'][$paletteName] = implode(';', $parts);
    }
}
