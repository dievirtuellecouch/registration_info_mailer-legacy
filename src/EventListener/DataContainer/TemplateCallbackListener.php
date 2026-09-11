<?php

declare(strict_types=1);

namespace MenAtWork\RegistrationInfoMailerBundle\EventListener\DataContainer;

use Contao\Config;
use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\CoreBundle\Framework\Adapter;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\DataContainer;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;

class TemplateCallbackListener
{
    private readonly Adapter $configAdapter;

    public function __construct(
        private readonly Connection $connection,
        ContaoFramework $framework,
    ) {
        $this->configAdapter = $framework->getAdapter(Config::class);
    }

    #[AsCallback(table: 'tl_module', target: 'fields.rim_mailtemplate.options')]
    public function getMemberRegistrationTemplates(?DataContainer $dataContainer = null): array
    {
        return $this->getMailTemplates(['member_registration_mail', 'member_registration']);
    }

    #[AsCallback(table: 'tl_module', target: 'fields.rim_change_mailtemplate.options')]
    public function getMemberChangeTemplates(?DataContainer $dataContainer = null): array
    {
        return $this->getMailTemplates('account_change_mail');
    }

    #[AsCallback(table: 'tl_module', target: 'fields.rim_act_mailtemplate.options')]
    public function getMemberActivationTemplates(?DataContainer $dataContainer = null): array
    {
        return $this->getMailTemplates(['member_activation_mail', 'member_activation']);
    }

    #[AsCallback(table: 'tl_settings', target: 'fields.rim_activate_mailtemplate_default.options')]
    #[AsCallback(table: 'tl_member', target: 'fields.rim_activate_mailtemplate.options')]
    public function getAccountActivationTemplates(?DataContainer $dataContainer = null): array
    {
        return $this->getMailTemplates('account_activation_mail');
    }

    #[AsCallback(table: 'tl_settings', target: 'fields.rim_deactivate_mailtemplate_default.options')]
    #[AsCallback(table: 'tl_member', target: 'fields.rim_deactivate_mailtemplate.options')]
    public function getAccountDeactivationTemplates(?DataContainer $dataContainer = null): array
    {
        return $this->getMailTemplates('account_deactivation_mail');
    }

    #[AsCallback(table: 'tl_member', target: 'fields.rim_activate_mailtemplate.load')]
    #[AsCallback(table: 'tl_member', target: 'fields.rim_deactivate_mailtemplate.load')]
    public function setDefaultTemplate(mixed $value, ?DataContainer $dataContainer = null): mixed
    {
        if (null === $dataContainer || (int) $value > 0) {
            return $value;
        }

        $default = $this->configAdapter->get($dataContainer->field.'_default');

        return '' !== (string) $default ? $default : $value;
    }

    #[AsCallback(table: 'tl_member', target: 'fields.rim_send_mail.load')]
    public function resetSendCheckbox(mixed $value): mixed
    {
        return $value;
    }

    /**
     * @return array<int, string>
     */
    private function getMailTemplates(string|array $type): array
    {
        if (\is_array($type)) {
            $rows = $this->connection->executeQuery(
                'SELECT id, title FROM tl_nc_notification WHERE type IN (?) ORDER BY title ASC',
                [$type],
                [ArrayParameterType::STRING],
            )->fetchAllAssociative();
        } else {
            $rows = $this->connection->fetchAllAssociative(
                'SELECT id, title FROM tl_nc_notification WHERE type = ? ORDER BY title ASC',
                [$type],
            );
        }

        $options = [];

        foreach ($rows as $row) {
            $options[(int) $row['id']] = (string) $row['title'];
        }

        return $options;
    }
}
