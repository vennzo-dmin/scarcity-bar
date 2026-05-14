<?php

namespace App\Domain\Notifications;

use App\Models\BisAlertSubscription;
use App\Models\BisDeliveryLog;
use App\Models\BisShopSetting;
use App\Models\User;
use RuntimeException;

class NotificationEngine
{
    public function __construct(
        private readonly TemplateRenderer $renderer,
        private readonly ProviderResolver $resolver,
    ) {}

    public function sendForAlert(BisAlertSubscription $alert): void
    {
        $shop = User::find($alert->user_id);
        if (!$shop) throw new RuntimeException('Shop not found for alert ' . $alert->id);

        $settings = BisShopSetting::firstOrCreate(
            ['user_id' => $alert->user_id],
            array_merge(['user_id' => $alert->user_id], BisShopSetting::defaults())
        );

        $channels = $this->resolveChannels($alert);

        foreach ($channels as $channel) {
            $subscriber = $alert->subscriber;
            if (!$subscriber || !$subscriber->canReceive($channel)) {
                $this->log($alert, $channel, null, BisDeliveryLog::STATUS_FAILED, 'consent_or_contact_missing', null);
                continue;
            }

            $provider = $this->resolver->resolve($settings, $channel);
            if (!$provider) {
                $this->log($alert, $channel, null, BisDeliveryLog::STATUS_FAILED, 'no_provider_registered', null);
                continue;
            }

            $rendered = $this->renderer->renderForAlert($shop, $settings, $alert, $channel);
            $to = $channel === 'email' ? $subscriber->email : $subscriber->phone;

            $payload = new NotificationPayload(
                channel: $channel,
                to: $to,
                subject: $rendered['subject'],
                body: $rendered['body'],
                context: ['alert_id' => $alert->id],
            );

            $result = $provider->send($payload);

            $this->log(
                $alert,
                $channel,
                $provider->name(),
                $result->ok ? BisDeliveryLog::STATUS_SENT : BisDeliveryLog::STATUS_FAILED,
                $result->error,
                $result->providerMessageId,
                $to,
            );
        }
    }

    private function resolveChannels(BisAlertSubscription $alert): array
    {
        $c = $alert->channel ?: 'email';
        return match ($c) {
            'both'  => ['email', 'sms'],
            'sms'   => ['sms'],
            default => ['email'],
        };
    }

    private function log(
        BisAlertSubscription $alert,
        string $channel,
        ?string $provider,
        string $status,
        ?string $error,
        ?string $providerMsgId,
        ?string $recipient = null
    ): void {
        BisDeliveryLog::create([
            'user_id'               => $alert->user_id,
            'alert_subscription_id' => $alert->id,
            'subscriber_id'         => $alert->subscriber_id,
            'type'                  => $alert->type,
            'channel'               => $channel,
            'provider'              => $provider,
            'recipient'             => $recipient ?? ($channel === 'email' ? $alert->subscriber?->email : $alert->subscriber?->phone),
            'status'                => $status,
            'provider_message_id'   => $providerMsgId,
            'error'                 => $error ? substr($error, 0, 490) : null,
            'sent_at'               => $status === BisDeliveryLog::STATUS_SENT ? now() : null,
        ]);
    }
}
