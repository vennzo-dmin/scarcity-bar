<?php

namespace App\Domain\Subscriptions;

use App\Models\BisAlertSubscription;
use Illuminate\Support\Collection;

class SubscriptionFinder
{
    public function eligibleForVariant(int $userId, string $type, int $variantId): Collection
    {
        return BisAlertSubscription::query()
            ->where('user_id', $userId)
            ->where('type', $type)
            ->where('variant_id', $variantId)
            ->where('status', BisAlertSubscription::STATUS_ACTIVE)
            ->with('subscriber')
            ->get();
    }

    public function eligibleForProduct(int $userId, string $type, int $productId): Collection
    {
        return BisAlertSubscription::query()
            ->where('user_id', $userId)
            ->where('type', $type)
            ->where('product_id', $productId)
            ->whereNull('variant_id')
            ->where('status', BisAlertSubscription::STATUS_ACTIVE)
            ->with('subscriber')
            ->get();
    }
}
