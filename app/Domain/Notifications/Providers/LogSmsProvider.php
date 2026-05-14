<?php

namespace App\Domain\Notifications\Providers;

use App\Domain\Notifications\Contracts\NotificationProvider;
use App\Domain\Notifications\NotificationPayload;
use App\Domain\Notifications\NotificationResult;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Default no-op SMS provider that logs the message. Replace with Twilio/MessageBird/etc.
 * via container binding without touching the engine.
 */
class LogSmsProvider implements NotificationProvider
{
    public function channel(): string { return 'sms'; }

    public function name(): string { return 'log_sms'; }

    public function send(NotificationPayload $payload): NotificationResult
    {
        Log::channel(config('bis.log_channel', 'stack'))
            ->info('[BIS][SMS]', [
                'to'   => $payload->to,
                'body' => $payload->body,
            ]);

        return NotificationResult::success('log-' . Str::uuid()->toString());
    }
}
