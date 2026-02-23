<?php

declare(strict_types=1);

namespace MenAtWork\RegistrationInfoMailerBundle\EventListener\DataContainer;

use Contao\Config;
use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\CoreBundle\Framework\Adapter;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\DataContainer;
use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Terminal42\NotificationCenterBundle\NotificationCenter;

class MemberSubmitCallbackListener
{
    private readonly Adapter $configAdapter;

    /**
     * @var array<int, array<string, mixed>>
     */
    private array $previousMemberState = [];

    public function __construct(
        private readonly Connection $connection,
        private readonly NotificationCenter $notificationCenter,
        private readonly LoggerInterface $logger,
        private readonly RequestStack $requestStack,
        ContaoFramework $framework,
    ) {
        $this->configAdapter = $framework->getAdapter(Config::class);
    }

    #[AsCallback(table: 'tl_member', target: 'config.onload')]
    public function onLoad(mixed $context = null): void
    {
        $this->storePreviousState($context);
    }

    #[AsCallback(table: 'tl_member', target: 'config.onbeforesubmit')]
    public function onBeforeSubmit(array $values, DataContainer $context): array
    {
        $this->storePreviousState($context);

        return $values;
    }

    #[AsCallback(table: 'tl_member', target: 'config.onsubmit')]
    public function onSubmit(mixed $context, mixed $module = null): void
    {
        $memberId = $this->extractMemberId($context);

        if ($memberId < 1) {
            return;
        }

        $memberData = $this->connection->fetchAssociative(
            'SELECT * FROM tl_member WHERE id = ?',
            [$memberId],
        );

        if (false === $memberData || '' === (string) ($memberData['rim_send_mail'] ?? '')) {
            return;
        }

        $previousState = $this->previousMemberState[$memberId] ?? null;
        $isDisableToggleRequest = $this->isDisableToggleRequest();

        if (null === $previousState && !$isDisableToggleRequest) {
            return;
        }

        $timestamp = time();
        $isActive = $this->isActive($memberData, $timestamp);

        if (null !== $previousState) {
            $wasActive = $this->isActive($previousState, $timestamp);

            if ($wasActive === $isActive) {
                return;
            }
        }

        $notificationId = $this->resolveNotificationId($memberData, $isActive);

        if ($notificationId < 1) {
            return;
        }

        $tokens = $this->buildTokens($memberData);
        $language = (string) ($memberData['language'] ?? '');

        try {
            $this->notificationCenter->sendNotification(
                $notificationId,
                $tokens,
                '' !== $language ? $language : null,
            );

            if (!$isActive) {
                $this->connection->update(
                    'tl_member',
                    ['rim_send_mail' => ''],
                    ['id' => $memberId],
                );
            }
        } catch (\Throwable $exception) {
            $this->logger->error('RIM: could not send member submit notification.', [
                'notificationId' => $notificationId,
                'memberId' => $memberId,
                'exception' => $exception,
            ]);
        }
    }

    /**
     * @param array<string, mixed> $memberData
     */
    private function isActive(array $memberData, int $timestamp): bool
    {
        if ('1' !== (string) ($memberData['login'] ?? '')) {
            return false;
        }

        if ('1' === (string) ($memberData['disable'] ?? '')) {
            return false;
        }

        $start = (int) ($memberData['start'] ?? 0);
        $stop = (int) ($memberData['stop'] ?? 0);

        if ($start > 0 && $start > $timestamp) {
            return false;
        }

        if ($stop > 0 && $stop <= $timestamp) {
            return false;
        }

        return true;
    }

    /**
     * @param array<string, mixed> $memberData
     *
     * @return array<string, mixed>
     */
    private function buildTokens(array $memberData): array
    {
        $tokens = [];

        foreach ($memberData as $key => $value) {
            if (null !== $value && '' !== (string) $value) {
                $tokens[$key] = $value;
            }
        }

        return $tokens;
    }

    private function extractMemberId(mixed $context): int
    {
        if ($context instanceof DataContainer && null !== $context->activeRecord) {
            return (int) ($context->activeRecord->id ?? 0);
        }

        if ($context instanceof DataContainer) {
            return (int) ($context->id ?? 0);
        }

        if (\is_object($context)) {
            try {
                return (int) ($context->id ?? 0);
            } catch (\Throwable) {
                return 0;
            }
        }

        $request = $this->requestStack->getCurrentRequest();

        if (null !== $request) {
            $requestId = (int) ($request->query->get('id') ?? $request->request->get('id') ?? 0);

            if ($requestId > 0) {
                return $requestId;
            }
        }

        return 0;
    }

    private function storePreviousState(mixed $context): void
    {
        $memberId = $this->extractMemberId($context);

        if ($memberId < 1 || isset($this->previousMemberState[$memberId])) {
            return;
        }

        $memberData = $this->connection->fetchAssociative(
            'SELECT * FROM tl_member WHERE id = ?',
            [$memberId],
        );

        if (false === $memberData) {
            return;
        }

        $this->previousMemberState[$memberId] = $memberData;
    }

    /**
     * @param array<string, mixed> $memberData
     */
    private function resolveNotificationId(array $memberData, bool $isActive): int
    {
        $field = $isActive ? 'rim_activate_mailtemplate' : 'rim_deactivate_mailtemplate';
        $notificationId = (int) ($memberData[$field] ?? 0);

        if ($notificationId > 0) {
            return $notificationId;
        }

        return (int) ($this->configAdapter->get($field.'_default') ?? 0);
    }

    private function isDisableToggleRequest(): bool
    {
        $request = $this->requestStack->getCurrentRequest();

        if (null === $request) {
            return false;
        }

        return 'toggle' === (string) $request->query->get('act')
            && 'disable' === (string) $request->query->get('field');
    }
}
