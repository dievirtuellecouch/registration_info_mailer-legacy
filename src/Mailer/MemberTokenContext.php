<?php

declare(strict_types=1);

namespace MenAtWork\RegistrationInfoMailerBundle\Mailer;

use Symfony\Contracts\Service\ResetInterface;

/** Keeps legacy insert tags scoped to the member whose message is being rendered. */
class MemberTokenContext implements ResetInterface
{
    private array $tokens = [];

    public function prepare(array $member): array
    {
        unset($member['password'], $member['session'], $member['autologin'], $member['secret'], $member['backupCodes']);
        $tokens = $member;

        foreach ($member as $name => $value) {
            $tokens['member_'.$name] = $value;
            $tokens['member_raw_'.$name] = $value;
        }

        return $tokens;
    }

    public function withTokens(array $tokens, callable $send): mixed
    {
        $previous = $this->tokens;
        $this->tokens = $tokens;

        try {
            return $send();
        } finally {
            $this->tokens = $previous;
        }
    }

    public function replace(string $tag): string|false
    {
        [$prefix, $field] = array_pad(explode('::', $tag, 2), 2, '');
        if (strtolower($prefix) !== 'rim') {
            return false;
        }

        $value = $this->tokens[$field] ?? '';

        return is_scalar($value) ? (string) $value : '';
    }

    public function reset(): void
    {
        $this->tokens = [];
    }
}
