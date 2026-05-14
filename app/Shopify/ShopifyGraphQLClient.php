<?php

namespace App\Shopify;

use App\Models\User;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class ShopifyGraphQLClient
{
    public function __construct(private readonly User $shop) {}

    public function query(string $query, array $variables = []): array
    {
        try {
            $response = $this->shop->api()->graph($query, $variables);
        } catch (\Throwable $e) {
            Log::channel(config('bis.log_channel', 'stack'))
                ->error('[BIS] GraphQL exception', ['shop' => $this->shop->name, 'err' => $e->getMessage()]);
            throw new RuntimeException('Shopify GraphQL call failed: ' . $e->getMessage(), 0, $e);
        }

        $body = $response['body'] ?? [];
        if (is_object($body) && method_exists($body, 'toArray')) {
            $body = $body->toArray();
        }

        if (!empty($body['errors'])) {
            Log::channel(config('bis.log_channel', 'stack'))
                ->warning('[BIS] GraphQL user errors', ['errors' => $body['errors']]);
            throw new RuntimeException('Shopify GraphQL returned errors: ' . json_encode($body['errors']));
        }

        return $body['data'] ?? [];
    }
}
