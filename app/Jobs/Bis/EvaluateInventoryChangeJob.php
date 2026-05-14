<?php

namespace App\Jobs\Bis;

use App\Domain\Inventory\InventoryEvaluator;
use App\Domain\Subscriptions\SubscriptionFinder;
use App\Models\BisAlertSubscription;
use App\Models\BisProductSnapshot;
use App\Models\BisShopSetting;
use App\Models\User;
use App\Shopify\ProductReader;
use App\Support\GidHelper;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class EvaluateInventoryChangeJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 30;

    public function __construct(
        public readonly int $userId,
        public readonly int $productId,
        public readonly ?int $variantId = null,
    ) {}

    public function handle(SubscriptionFinder $finder): void
    {
        $lockKey = "bis:inv_eval:{$this->userId}:{$this->productId}:{$this->variantId}";
        $lock = Cache::lock($lockKey, 30);
        if (!$lock->get()) return;

        try {
            $shop = User::find($this->userId);
            if (!$shop) return;

            $settings = BisShopSetting::firstOrCreate(
                ['user_id' => $this->userId],
                array_merge(['user_id' => $this->userId], BisShopSetting::defaults())
            );

            $reader = new ProductReader($shop);
            $product = $reader->fetchProductWithVariants($this->productId);
            if (!$product) return;

            $evaluator = new InventoryEvaluator($settings);
            $variants  = $product['variants']['edges'] ?? [];

            foreach ($variants as $vEdge) {
                $v = $vEdge['node'];
                $vid = GidHelper::extractId($v['id'] ?? null);
                if (!$vid) continue;
                if ($this->variantId && $vid !== $this->variantId) continue;

                $decision = $evaluator->evaluateVariant($v);

                $this->upsertSnapshot($product, $v, $decision->total, $decision->sellable);

                if (!$decision->eligible) continue;

                $subs = $finder->eligibleForVariant(
                    $this->userId,
                    BisAlertSubscription::TYPE_BACK_IN_STOCK,
                    $vid,
                );

                if ($subs->isEmpty()) continue;

                BisAlertSubscription::whereIn('id', $subs->pluck('id'))
                    ->update(['status' => BisAlertSubscription::STATUS_QUEUED]);

                DispatchAlertBatchJob::dispatch(
                    $this->userId,
                    BisAlertSubscription::TYPE_BACK_IN_STOCK,
                    $subs->pluck('id')->all(),
                );
            }
        } catch (\Throwable $e) {
            Log::channel(config('bis.log_channel', 'stack'))
                ->error('[BIS] EvaluateInventoryChangeJob failed', [
                    'shop'    => $this->userId,
                    'product' => $this->productId,
                    'err'     => $e->getMessage(),
                ]);
            throw $e;
        } finally {
            $lock->release();
        }
    }

    private function upsertSnapshot(array $product, array $variant, int $total, int $sellable): void
    {
        $pid = GidHelper::extractId($product['id'] ?? null);
        $vid = GidHelper::extractId($variant['id'] ?? null);
        if (!$pid || !$vid) return;

        // Shopify's inventory_levels/update webhook carries inventory_item_id
        // (not variant_id) so we persist it here to make the reverse lookup
        // O(1) — see WebhookController::inventoryLevelsUpdate.
        $iid = GidHelper::extractId($variant['inventoryItem']['id'] ?? null);

        BisProductSnapshot::updateOrCreate(
            ['user_id' => $this->userId, 'variant_id' => $vid],
            [
                'product_id'         => $pid,
                'inventory_item_id'  => $iid ?: null,
                'inventory_total'    => $total,
                'inventory_sellable' => $sellable,
                'price'              => isset($variant['price']) ? (float)$variant['price'] : null,
                'compare_at_price'   => isset($variant['compareAtPrice']) ? (float)$variant['compareAtPrice'] : null,
                'available'          => (bool)($variant['availableForSale'] ?? false),
                'checked_at'         => now(),
            ]
        );
    }
}
