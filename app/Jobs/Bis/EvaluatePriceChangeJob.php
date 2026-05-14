<?php

namespace App\Jobs\Bis;

use App\Domain\Pricing\PriceChangeEvaluator;
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

class EvaluatePriceChangeJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 30;

    public function __construct(
        public readonly int $userId,
        public readonly int $productId,
    ) {}

    public function handle(): void
    {
        $lockKey = "bis:price_eval:{$this->userId}:{$this->productId}";
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

            $evaluator = new PriceChangeEvaluator($settings);

            foreach ($product['variants']['edges'] ?? [] as $edge) {
                $v = $edge['node'];
                $vid = GidHelper::extractId($v['id'] ?? null);
                if (!$vid) continue;

                $newPrice     = isset($v['price']) ? (float)$v['price'] : null;
                $newCompareAt = isset($v['compareAtPrice']) ? (float)$v['compareAtPrice'] : null;

                $prev = BisProductSnapshot::where('user_id', $this->userId)
                    ->where('variant_id', $vid)->first();

                $oldPrice     = $prev?->price !== null ? (float)$prev->price : null;
                $oldCompareAt = $prev?->compare_at_price !== null ? (float)$prev->compare_at_price : null;

                $iid = GidHelper::extractId($v['inventoryItem']['id'] ?? null);

                $update = [
                    'product_id'       => GidHelper::extractId($product['id']),
                    'price'            => $newPrice,
                    'compare_at_price' => $newCompareAt,
                    'available'        => (bool)($v['availableForSale'] ?? false),
                    'checked_at'       => now(),
                ];
                // Don't overwrite an existing inventory_item_id with null if
                // this payload didn't include the inventoryItem node.
                if ($iid) {
                    $update['inventory_item_id'] = $iid;
                }

                BisProductSnapshot::updateOrCreate(
                    ['user_id' => $this->userId, 'variant_id' => $vid],
                    $update
                );

                $activeSubs = BisAlertSubscription::where('user_id', $this->userId)
                    ->where('type', BisAlertSubscription::TYPE_PRICE_DROP)
                    ->where('variant_id', $vid)
                    ->where('status', BisAlertSubscription::STATUS_ACTIVE)
                    ->get();

                if ($activeSubs->isEmpty()) continue;

                $eligibleIds = [];
                foreach ($activeSubs as $sub) {
                    $decision = $evaluator->evaluate(
                        $oldPrice,
                        $oldCompareAt,
                        $newPrice,
                        $newCompareAt,
                        $sub->price_at_subscription ? (float)$sub->price_at_subscription : null,
                    );
                    if ($decision->eligible) $eligibleIds[] = $sub->id;
                }

                if (empty($eligibleIds)) continue;

                BisAlertSubscription::whereIn('id', $eligibleIds)
                    ->update(['status' => BisAlertSubscription::STATUS_QUEUED]);

                DispatchAlertBatchJob::dispatch(
                    $this->userId,
                    BisAlertSubscription::TYPE_PRICE_DROP,
                    $eligibleIds,
                );
            }
        } catch (\Throwable $e) {
            Log::channel(config('bis.log_channel', 'stack'))
                ->error('[BIS] EvaluatePriceChangeJob failed', [
                    'shop'    => $this->userId,
                    'product' => $this->productId,
                    'err'     => $e->getMessage(),
                ]);
            throw $e;
        } finally {
            $lock->release();
        }
    }
}
