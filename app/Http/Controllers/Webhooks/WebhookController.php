<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Jobs\AppUninstalledJob;
use App\Jobs\Bis\EvaluateInventoryChangeJob;
use App\Jobs\Bis\EvaluatePriceChangeJob;
use App\Models\BisAttributionEvent;
use App\Models\BisDeliveryLog;
use App\Models\BisSubscriber;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    public function productsUpdate(Request $request): JsonResponse
    {
        $shop = $this->resolveShop($request);
        if (!$shop) return $this->ack();

        $payload = $request->all();
        if (!$this->idempotent('products_update', $shop->id, $payload)) return $this->ack();

        $productId = (int)($payload['id'] ?? 0);
        if ($productId > 0) {
            EvaluateInventoryChangeJob::dispatch($shop->id, $productId);
            EvaluatePriceChangeJob::dispatch($shop->id, $productId);
        }

        return $this->ack();
    }

    public function inventoryLevelsUpdate(Request $request): JsonResponse
    {
        $shop = $this->resolveShop($request);
        if (!$shop) return $this->ack();

        $payload = $request->all();
        if (!$this->idempotent('inventory_levels_update', $shop->id, $payload)) return $this->ack();

        $inventoryItemId = (int)($payload['inventory_item_id'] ?? 0);
        if ($inventoryItemId > 0) {
            // Shopify's payload only has inventory_item_id; we resolve the
            // variant/product via the snapshot index populated by earlier
            // EvaluateInventoryChangeJob / EvaluatePriceChangeJob runs.
            $snapshot = \App\Models\BisProductSnapshot::where('user_id', $shop->id)
                ->where('inventory_item_id', $inventoryItemId)
                ->first();

            if (!$snapshot) {
                // First time we've ever seen this inventory item for this
                // shop — stash a flag so the next evaluation for its
                // product backfills the mapping, and ack the webhook.
                Cache::put("bis:pending_inv:{$shop->id}:{$inventoryItemId}", true, 600);
                return $this->ack();
            }

            EvaluateInventoryChangeJob::dispatch($shop->id, $snapshot->product_id, $snapshot->variant_id);
        }

        return $this->ack();
    }

    public function ordersCreate(Request $request): JsonResponse
    {
        $shop = $this->resolveShop($request);
        if (!$shop) return $this->ack();

        $payload = $request->all();
        if (!$this->idempotent('orders_create', $shop->id, $payload)) return $this->ack();

        $email = $payload['email'] ?? null;
        $phone = $payload['phone'] ?? null;
        if (!$email && !$phone) return $this->ack();

        $subscriber = BisSubscriber::where('user_id', $shop->id)
            ->when($email, fn($q) => $q->where('email', $email))
            ->when(!$email && $phone, fn($q) => $q->where('phone', $phone))
            ->first();

        if (!$subscriber) return $this->ack();

        $windowHours = (int)config('bis.attribution_window_hours', 96);

        $recentClick = BisDeliveryLog::where('user_id', $shop->id)
            ->where('subscriber_id', $subscriber->id)
            ->where('status', BisDeliveryLog::STATUS_CLICKED)
            ->where('created_at', '>=', now()->subHours($windowHours))
            ->latest('id')
            ->first();

        if (!$recentClick) return $this->ack();

        BisAttributionEvent::create([
            'user_id'               => $shop->id,
            'alert_subscription_id' => $recentClick->alert_subscription_id,
            'delivery_log_id'       => $recentClick->id,
            'subscriber_id'         => $subscriber->id,
            'event'                 => 'order',
            'order_id'              => (int)($payload['id'] ?? 0),
            'amount'                => (float)($payload['total_price'] ?? 0),
            'currency'              => $payload['currency'] ?? null,
            'occurred_at'           => now(),
        ]);

        return $this->ack();
    }

    public function appUninstalled(Request $request): JsonResponse
    {
        $shop = $this->resolveShop($request);
        if ($shop) {
            // App-specific cleanup: stop delivering widget on storefront.
            \App\Models\BisShopSetting::where('user_id', $shop->id)->update(['is_enabled' => false]);
            Log::channel(config('bis.log_channel', 'stack'))->info('[BIS] app/uninstalled', ['shop' => $shop->name]);
        }

        // Delegate to Kyon147's built-in cleanup (cancels active plan,
        // purges API token, soft-deletes the shop record, fires the
        // AppUninstalledEvent) via the App\Jobs bridge class.
        $shopDomain = $request->header('x-shopify-shop-domain') ?: $shop?->name;
        if ($shopDomain) {
            AppUninstalledJob::dispatch($shopDomain, (object) $request->all());
        }

        return $this->ack();
    }

    public function customersDataRequest(Request $request): JsonResponse
    {
        $shop = $this->resolveShop($request);
        Log::channel(config('bis.log_channel', 'stack'))
            ->info('[BIS] customers/data_request', ['shop' => $shop?->name, 'payload' => $request->all()]);
        return $this->ack();
    }

    public function customersRedact(Request $request): JsonResponse
    {
        $shop = $this->resolveShop($request);
        if (!$shop) return $this->ack();

        $customer = $request->input('customer', []);
        $email = $customer['email'] ?? null;
        $phone = $customer['phone'] ?? null;

        BisSubscriber::where('user_id', $shop->id)
            ->when($email, fn($q) => $q->orWhere('email', $email))
            ->when($phone, fn($q) => $q->orWhere('phone', $phone))
            ->delete();

        return $this->ack();
    }

    public function shopRedact(Request $request): JsonResponse
    {
        $shopDomain = $request->input('shop_domain');
        if (!$shopDomain) return $this->ack();

        $shop = User::where('name', $shopDomain)->first();
        if (!$shop) return $this->ack();

        foreach ([
            \App\Models\BisAlertSubscription::class,
            \App\Models\BisSubscriber::class,
            \App\Models\BisDeliveryLog::class,
            \App\Models\BisProductSnapshot::class,
            \App\Models\BisAttributionEvent::class,
            \App\Models\BisAutomationRule::class,
            \App\Models\BisShopSetting::class,
            \App\Models\BisNotificationTemplate::class,
        ] as $model) {
            $model::where('user_id', $shop->id)->delete();
        }

        return $this->ack();
    }

    private function resolveShop(Request $request): ?User
    {
        $domain = $request->header('x-shopify-shop-domain') ?? $request->input('shop_domain');
        if (!$domain) return null;
        return User::where('name', $domain)->first();
    }

    private function idempotent(string $topic, int $shopId, array $payload): bool
    {
        $hash = md5(json_encode($payload));
        $key  = "bis:wh:{$topic}:{$shopId}:{$hash}";
        $lock = Cache::lock($key, 30);
        if (!$lock->get()) return false;
        Cache::put($key, true, 300);
        return true;
    }

    private function ack(): JsonResponse
    {
        return response()->json(['ok' => true]);
    }
}
