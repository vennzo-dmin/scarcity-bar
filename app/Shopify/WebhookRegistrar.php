<?php

namespace App\Shopify;

use App\Models\User;
use Illuminate\Support\Facades\Log;

class WebhookRegistrar
{
    public const TOPICS = [
        'products/update',
        'inventory_levels/update',
        'orders/create',
        'orders/paid',
        'app/uninstalled',
    ];

    public function __construct(private readonly User $shop) {}

    public function registerAll(): void
    {
        foreach (self::TOPICS as $topic) {
            try {
                $this->register($topic);
            } catch (\Throwable $e) {
                Log::channel(config('bis.log_channel', 'stack'))
                    ->warning('[BIS] Failed to register webhook', [
                        'shop'  => $this->shop->name,
                        'topic' => $topic,
                        'err'   => $e->getMessage(),
                    ]);
            }
        }
    }

    public function register(string $topic): void
    {
        $endpoint = url('/webhook/' . str_replace('/', '-', $topic));

        $mutation = <<<'GQL'
        mutation($topic: WebhookSubscriptionTopic!, $sub: WebhookSubscriptionInput!) {
          webhookSubscriptionCreate(topic: $topic, webhookSubscription: $sub) {
            userErrors { field message }
            webhookSubscription { id }
          }
        }
        GQL;

        $topicEnum = strtoupper(str_replace('/', '_', $topic));

        (new ShopifyGraphQLClient($this->shop))->query($mutation, [
            'topic' => $topicEnum,
            'sub'   => [
                'callbackUrl' => $endpoint,
                'format'      => 'JSON',
            ],
        ]);
    }
}
