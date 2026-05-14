<?php

namespace App\Domain\Notifications;

use App\Domain\Notifications\Contracts\NotificationProvider;
use App\Domain\Notifications\Providers\LogSmsProvider;
use App\Domain\Notifications\Providers\MailProvider;
use App\Domain\Notifications\Providers\MessageBirdSmsProvider;
use App\Domain\Notifications\Providers\SmtpMailProvider;
use App\Domain\Notifications\Providers\TwilioSmsProvider;
use App\Models\BisShopSetting;

/**
 * Resolves the correct notification provider for a given shop + channel
 * based on merchant-configured credentials stored in bis_shop_settings.providers.
 *
 * This is the tenant-aware bridge between the engine and provider implementations.
 */
class ProviderResolver
{
    public function resolve(BisShopSetting $settings, string $channel): ?NotificationProvider
    {
        $providers = $settings->providers ?? [];

        return match ($channel) {
            'email' => $this->resolveEmail($providers['email'] ?? []),
            'sms'   => $this->resolveSms($providers['sms'] ?? []),
            default => null,
        };
    }

    private function resolveEmail(array $cfg): NotificationProvider
    {
        $driver = $cfg['driver'] ?? 'default';

        if ($driver === 'smtp'
            && !empty($cfg['smtp_host'])
            && !empty($cfg['smtp_user'])
            && !empty($cfg['smtp_pass'])
        ) {
            return new SmtpMailProvider($cfg);
        }

        return new MailProvider();
    }

    private function resolveSms(array $cfg): NotificationProvider
    {
        $driver = $cfg['driver'] ?? 'log';

        if ($driver === 'twilio') {
            return new TwilioSmsProvider($cfg['twilio'] ?? []);
        }

        if ($driver === 'messagebird') {
            return new MessageBirdSmsProvider($cfg['messagebird'] ?? []);
        }

        return new LogSmsProvider();
    }
}
