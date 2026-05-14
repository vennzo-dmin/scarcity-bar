<?php

namespace App\Http\Controllers\Storefront;

use App\Domain\Subscriptions\SubscribeAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\StorefrontSubscribeRequest;
use App\Models\BisDeliveryLog;
use App\Models\BisShopSetting;
use App\Models\BisSubscriber;
use App\Models\User;
use App\Shopify\ProductReader;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class StorefrontApiController extends Controller
{
    public function widgetConfig(Request $request): JsonResponse
    {
        $shop = $this->resolveShop($request->query('shop'));
        if (!$shop) return response()->json(['ok' => false], 404);

        $settings = BisShopSetting::firstOrCreate(
            ['user_id' => $shop->id],
            array_merge(['user_id' => $shop->id], BisShopSetting::defaults())
        );

        // Storefront copy: defaults overlaid with whatever the merchant has
        // saved. One dictionary, used on every product page.
        $translations = array_replace(
            BisShopSetting::translationDefaults(),
            (array) ($settings->translations ?? [])
        );

        $w = (array) ($settings->widget ?? []);

        $pages = [
            'product'    => (bool)($w['page_product'] ?? true),
            'collection' => (bool)($w['page_collection'] ?? true),
            'index'      => (bool)($w['page_index'] ?? true),
            'search'     => (bool)($w['page_search'] ?? true),
        ];

        $targeting = [
            'mode'            => (string)($w['targeting_mode'] ?? 'all'),
            'product_ids'     => array_values(array_filter(array_map('intval', (array)($w['target_product_ids'] ?? [])))),
            'collection_ids'  => array_values(array_filter(array_map('intval', (array)($w['target_collection_ids'] ?? [])))),
        ];

        $scarcity = [
            'max_stock'       => max(1, (int)($w['scarcity_max_stock'] ?? 50)),
            'low_threshold'   => max(1, (int)($w['scarcity_low_threshold'] ?? 10)),
            'bar_radius'      => max(0, (int)($w['scarcity_bar_radius'] ?? 999)),
            'track_color'     => (string)($w['scarcity_track_color'] ?? '#E5E7EB'),
            'color_in_stock'  => (string)($w['scarcity_color_in_stock'] ?? '#16A34A'),
            'color_low_stock' => (string)($w['scarcity_color_low_stock'] ?? '#D97706'),
            'color_sold_out'  => (string)($w['scarcity_color_sold_out'] ?? '#DC2626'),
            'animate_bar'     => (bool)($w['scarcity_animate_bar'] ?? true),
            'show_pulse'      => (bool)($w['scarcity_show_pulse'] ?? true),
        ];

        $pricing = (array) ($settings->pricing ?? []);

        return response()->json([
            'ok'           => true,
            'enabled'      => (bool)$settings->is_enabled,
            'widget'       => $settings->widget,
            'consent'      => $settings->consent,
            'translations' => $translations,
            'pages'        => $pages,
            'targeting'    => $targeting,
            'scarcity'     => $scarcity,
            'pricing'      => [
                'min_abs_drop'   => (float)($pricing['min_abs_drop'] ?? 0.5),
                'min_pct_drop'   => (float)($pricing['min_pct_drop'] ?? 5),
                'use_compare_at' => (bool)($pricing['use_compare_at'] ?? true),
            ],
            'features'     => [
                'back_in_stock' => true,
                'price_drop'    => (bool)data_get($settings->widget, 'show_on_price_drop', true),
            ],
        ])->withHeaders($this->corsHeaders());
    }

    /**
     * Batch inventory for listing grids (index pages where Liquid cannot enumerate products).
     */
    public function listingInventory(Request $request): JsonResponse
    {
        $shop = $this->resolveShop($request->query('shop'));
        if (!$shop) {
            return response()->json(['ok' => false], 404)->withHeaders($this->corsHeaders());
        }

        $settings = BisShopSetting::where('user_id', $shop->id)->first();
        if (!$settings || !$settings->is_enabled) {
            return response()->json(['ok' => false, 'error' => 'disabled'], 403)->withHeaders($this->corsHeaders());
        }

        $raw = (string) $request->query('handles', '');
        $handles = array_values(array_unique(array_filter(array_map(static function ($h) {
            return strtolower(trim((string) $h));
        }, preg_split('/[\s,]+/', $raw)))));

        $handles = array_slice($handles, 0, 18);
        if ($handles === []) {
            return response()->json(['ok' => true, 'products' => []])->withHeaders($this->corsHeaders());
        }

        try {
            $reader = new ProductReader($shop);
            $products = $reader->fetchProductsInventoryByHandles($handles);
        } catch (\Throwable $e) {
            Log::channel(config('bis.log_channel', 'stack'))
                ->warning('[BIS] listingInventory failed', ['err' => $e->getMessage()]);

            return response()->json(['ok' => false, 'error' => 'lookup_failed'], 500)->withHeaders($this->corsHeaders());
        }

        // Respect targeting for each row — omit products that should never render.
        $filtered = [];
        foreach ($products as $handle => $row) {
            $pid = (int)($row['id'] ?? 0);
            $cids = (array)($row['collection_ids'] ?? []);
            if ($settings->passesProductTargeting($pid, $cids)) {
                $filtered[$handle] = $row;
            }
        }

        return response()->json([
            'ok'       => true,
            'products' => $filtered,
        ])->withHeaders($this->corsHeaders());
    }

    public function subscribe(StorefrontSubscribeRequest $request, SubscribeAction $action): JsonResponse
    {
        $shop = $this->resolveShop($request->input('shop'));
        if (!$shop) return response()->json(['ok' => false, 'error' => 'shop_not_found'], 404);

        $settings = BisShopSetting::where('user_id', $shop->id)->first();
        if (!$settings || !$settings->is_enabled) {
            return response()->json(['ok' => false, 'error' => 'widget_disabled'], 403)
                ->withHeaders($this->corsHeaders());
        }

        if (!$this->passesSubscribeTargeting($settings, $request)) {
            return response()->json(['ok' => false, 'error' => 'targeting_blocked'], 403)
                ->withHeaders($this->corsHeaders());
        }

        try {
            $alert = $action->execute($shop->id, $request->validated());
        } catch (\Throwable $e) {
            Log::channel(config('bis.log_channel', 'stack'))
                ->warning('[BIS] subscribe failed', ['err' => $e->getMessage()]);
            return response()->json(['ok' => false, 'error' => 'subscribe_failed'], 500)
                ->withHeaders($this->corsHeaders());
        }

        $translations = array_replace(
            BisShopSetting::translationDefaults(),
            (array) ($settings->translations ?? [])
        );

        return response()->json([
            'ok'                => true,
            'message'           => (string)($translations['success_message'] ?? "You're on the list!"),
            'subscription_id'   => $alert->id,
        ])->withHeaders($this->corsHeaders());
    }

    public function unsubscribe(Request $request, string $token): JsonResponse
    {
        $sub = BisSubscriber::where('unsubscribe_token', $token)->first();
        if (!$sub) return response()->json(['ok' => false], 404);

        $channel = $request->query('channel', 'email');
        if ($channel === 'sms') $sub->sms_unsubscribed_at = now();
        else                    $sub->email_unsubscribed_at = now();
        $sub->save();

        return response()->json(['ok' => true, 'message' => 'You have been unsubscribed.']);
    }

    public function trackClick(Request $request): \Symfony\Component\HttpFoundation\Response
    {
        $alertId = (int)$request->query('alert');
        $alert = \App\Models\BisAlertSubscription::find($alertId);
        if ($alert) {
            BisDeliveryLog::create([
                'user_id'               => $alert->user_id,
                'alert_subscription_id' => $alert->id,
                'subscriber_id'         => $alert->subscriber_id,
                'type'                  => $alert->type,
                'channel'               => 'email',
                'status'                => BisDeliveryLog::STATUS_CLICKED,
                'recipient'             => $alert->subscriber?->email,
                'sent_at'               => now(),
            ]);

            $shop = User::find($alert->user_id);
            $url = 'https://' . ($shop?->name ?? 'shopify.com') . '/products/' . ($alert->product_handle ?? '');
            return redirect()->away($url);
        }

        return redirect('/');
    }

    public function options(): JsonResponse
    {
        return response()->json(['ok' => true])->withHeaders($this->corsHeaders());
    }

    private function resolveShop(?string $domain): ?User
    {
        if (!$domain) return null;
        return User::where('name', $domain)->first();
    }

    private function corsHeaders(): array
    {
        return [
            'Access-Control-Allow-Origin'  => '*',
            'Access-Control-Allow-Methods' => 'GET, POST, OPTIONS',
            'Access-Control-Allow-Headers' => 'Content-Type, X-Requested-With',
        ];
    }

    private function passesSubscribeTargeting(BisShopSetting $settings, StorefrontSubscribeRequest $request): bool
    {
        $w = (array) ($settings->widget ?? []);
        $mode = (string)($w['targeting_mode'] ?? 'all');
        if ($mode === 'all') {
            return true;
        }

        $pid = (int) $request->input('product_id');
        if ($mode === 'products') {
            $ids = array_map('intval', (array)($w['target_product_ids'] ?? []));

            return in_array($pid, $ids, true);
        }

        if ($mode === 'collections') {
            $ids = array_map('intval', (array)($w['target_collection_ids'] ?? []));
            $incoming = array_map('intval', (array) $request->input('product_collection_ids', []));
            if ($ids === [] || $incoming === []) {
                return false;
            }

            return count(array_intersect($ids, $incoming)) > 0;
        }

        return true;
    }
}
