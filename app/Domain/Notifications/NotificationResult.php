<?php

namespace App\Domain\Notifications;

final class NotificationResult
{
    public function __construct(
        public readonly bool $ok,
        public readonly ?string $providerMessageId = null,
        public readonly ?string $error = null,
    ) {}

    public static function success(?string $id = null): self
    {
        return new self(true, $id);
    }

    public static function failure(string $error): self
    {
        return new self(false, null, $error);
    }
}
