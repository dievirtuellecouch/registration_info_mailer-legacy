<?php

declare(strict_types=1);

namespace MenAtWork\RegistrationInfoMailerBundle\EventListener;

use Contao\Controller;
use Contao\CoreBundle\DependencyInjection\Attribute\AsHook;
use Contao\CoreBundle\Framework\Adapter;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\MemberModel;
use Contao\Module;
use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;
use Terminal42\NotificationCenterBundle\NotificationCenter;

class RegistrationInfoMailerHookListener
{
    private readonly Adapter $controllerAdapter;

    /**
     * @var array<string, mixed>
     */
    private array $memberTokens = [];

    public function __construct(
        private readonly Connection $connection,
        private readonly NotificationCenter $notificationCenter,
        private readonly LoggerInterface $logger,
        ContaoFramework $framework,
    ) {
        $this->controllerAdapter = $framework->getAdapter(Controller::class);
    }

    #[AsHook('createNewUser')]
    public function onCreateNewUser(int $memberId, array $memberData, Module $module): void
    {
        if (!$this->isModuleFlagEnabled($module, 'rim_active')) {
            return;
        }

        $this->memberTokens = $memberData;
        $this->memberTokens['id'] = $memberId;

        $notificationId = (int) ($module->rim_mailtemplate ?? 0);

        if ($notificationId < 1) {
            $this->log(
                'RIM: failed to send the registration mail. The module needs more email information. Please check the module configuration.',
                __METHOD__,
                'ERROR',
            );

            return;
        }

        $this->sendNotification($notificationId, $this->memberTokens, (string) ($memberData['language'] ?? ''));

        if ($this->isModuleFlagEnabled($module, 'rim_do_syslog')) {
            $this->log(
                'RIM: a registration info mail has been send. Check your email.log for more information.',
                __METHOD__,
                'GENERAL',
            );
        }
    }

    #[AsHook('updatePersonalData')]
    public function onUpdatePersonalData(object $member, array $formData, ?Module $module = null): void
    {
        $this->sendChangeMail($member, $module);
    }

    #[AsHook('setNewPassword')]
    public function onSetNewPassword(object $member, mixed $formData = null, ?Module $module = null): void
    {
        $this->sendChangeMail($member, $module);
    }

    #[AsHook('activateAccount')]
    public function onActivateAccount(MemberModel $member, Module $module): void
    {
        if (!$this->isModuleFlagEnabled($module, 'rim_act_active')) {
            return;
        }

        if (!$this->loadMemberTokensById((int) $member->id)) {
            return;
        }

        $notificationId = (int) ($module->rim_act_mailtemplate ?? 0);

        if ($notificationId < 1) {
            $this->log(
                'RIM: failed to send the activation mail. The module needs more email informations. Please check the module configuration.',
                __METHOD__,
                'ERROR',
            );

            return;
        }

        $this->sendNotification($notificationId, $this->memberTokens, (string) ($member->language ?? ''));

        if ($this->isModuleFlagEnabled($module, 'rim_act_do_syslog')) {
            $this->log(
                'RIM: An activation info mail for the user '.$member->id.' has been send.',
                __METHOD__,
                'GENERAL',
            );
        }
    }

    #[AsHook('replaceInsertTags')]
    public function onReplaceInsertTags(string $tag): string|false
    {
        $parts = explode('::', $tag);

        if ('rim' !== ($parts[0] ?? null)) {
            return false;
        }

        if (empty($parts[1])) {
            return '';
        }

        return (string) ($this->memberTokens[$parts[1]] ?? '');
    }

    #[AsHook('loadLanguageFile')]
    public function onLoadLanguageFile(string $name, ?string $language = null): void
    {
        if ('explain' !== $name) {
            return;
        }

        $this->controllerAdapter->loadLanguageFile('tl_member');

        $removeTags = [
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
            'createdOn',
        ];

        try {
            $fields = array_keys($this->connection->createSchemaManager()->listTableColumns('tl_member'));
        } catch (\Throwable) {
            return;
        }

        $GLOBALS['TL_LANG']['XPL']['rim_helper'] = [];

        foreach ($fields as $field) {
            if (\in_array($field, $removeTags, true)) {
                continue;
            }

            $label = (string) ($GLOBALS['TL_LANG']['tl_member'][$field][0] ?? $field);
            $description = (string) ($GLOBALS['TL_LANG']['tl_member'][$field][1] ?? '');
            $text = '<strong>'.$label.'</strong>';

            if ('' !== $description) {
                $text .= ' - '.$description;
            }

            $GLOBALS['TL_LANG']['XPL']['rim_helper'][] = [
                '{{rim::'.$field.'}}',
                $text,
            ];
        }
    }

    private function sendChangeMail(object $member, ?Module $module = null): void
    {
        if (null === $module || !$this->isModuleFlagEnabled($module, 'rim_change_active')) {
            return;
        }

        $memberId = $this->extractMemberId($member);

        if (!$this->loadMemberTokensById($memberId)) {
            return;
        }

        $notificationId = (int) ($module->rim_change_mailtemplate ?? 0);

        if ($notificationId < 1) {
            $this->log(
                'RIM: failed to send the change mail. The module needs more email information. Please check the module configuration.',
                __METHOD__,
                'ERROR',
            );

            return;
        }

        $language = $this->extractLanguage($member);
        $this->sendNotification($notificationId, $this->memberTokens, $language);

        if ($this->isModuleFlagEnabled($module, 'rim_change_do_syslog')) {
            $this->log(
                'RIM: An change info mail for the user '.$memberId.' has been send.',
                __METHOD__,
                'GENERAL',
            );
        }
    }

    private function sendNotification(int $notificationId, array $tokens, string $language = ''): void
    {
        try {
            $this->notificationCenter->sendNotification(
                $notificationId,
                $tokens,
                '' !== $language ? $language : null,
            );
        } catch (\Throwable $exception) {
            $this->logger->error('RIM: could not send notification.', [
                'notificationId' => $notificationId,
                'exception' => $exception,
            ]);
        }
    }

    private function isModuleFlagEnabled(Module $module, string $field): bool
    {
        return '1' === (string) ($module->{$field} ?? '');
    }

    private function extractMemberId(object $member): int
    {
        try {
            return (int) ($member->id ?? 0);
        } catch (\Throwable) {
            return 0;
        }
    }

    private function extractLanguage(object $member): string
    {
        try {
            return (string) ($member->language ?? '');
        } catch (\Throwable) {
            return '';
        }
    }

    private function loadMemberTokensById(int $memberId): bool
    {
        if ($memberId < 1) {
            return false;
        }

        $memberData = $this->connection->fetchAssociative(
            'SELECT * FROM tl_member WHERE id = ?',
            [$memberId],
        );

        if (false === $memberData) {
            return false;
        }

        $tokens = [];

        foreach ($memberData as $key => $value) {
            if (null !== $value && '' !== (string) $value) {
                $tokens[$key] = $value;
            }
        }

        $this->memberTokens = $tokens;

        return true;
    }

    private function log(string $message, string $function, string $level): void
    {
        $this->controllerAdapter->log($message, $function, $level);
    }
}
