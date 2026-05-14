<?php

namespace App\Domain\Notifications;

final class NotificationPayload
{
    public function __construct(
        public readonly string $channel,
        public readonly string $to,
        public readonly ?string $subject,
        public readonly string $body,
        public readonly array $context = [],
    ) {}
}
