<?php

declare(strict_types=1);

namespace MenAtWork\RegistrationInfoMailerBundle\Mailer;

use Psr\Log\LoggerInterface;
use Terminal42\NotificationCenterBundle\NotificationCenter;

class MemberNotificationSender
{
    public function __construct(
        private readonly NotificationCenter $notificationCenter,
        private readonly MemberTokenContext $tokens,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function send(int $notificationId, array $member, string $language = ''): bool
    {
        $tokens = $this->tokens->prepare($member);

        try {
            $receipts = $this->tokens->withTokens($tokens, fn () => $this->notificationCenter->sendNotification(
                $notificationId,
                $tokens,
                $language !== '' ? $language : null,
            ));

            if (count($receipts) > 0 && $receipts->wereAllDelivered()) {
                return true;
            }

            $this->logger->warning('RIM: notification was not delivered to every recipient.', ['notificationId' => $notificationId]);
        } catch (\Throwable $exception) {
            $this->logger->error('RIM: could not send notification.', ['notificationId' => $notificationId, 'exception' => $exception]);
        }

        return false;
    }
}
