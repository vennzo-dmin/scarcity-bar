<?php

namespace App\Domain\Pricing;

use App\Models\BisShopSetting;

class PriceChangeEvaluator
{
    public function __construct(private readonly BisShopSetting $settings) {}

    /**
     * Compare a previous snapshot against a newly observed price.
     * Returns a PriceDecision describing whether this qualifies as a valid price drop.
     */
    public function evaluate(
        ?float $oldPrice,
        ?float $oldCompareAt,
        ?float $newPrice,
        ?float $newCompareAt,
        ?float $subscribedPrice = null
    ): PriceDecision {
        $cfg          = $this->settings->pricing ?? [];
        $minAbs       = (float)($cfg['min_abs_drop'] ?? 0.50);
        $minPct       = (float)($cfg['min_pct_drop'] ?? 5);
        $useCompareAt = (bool)($cfg['use_compare_at'] ?? true);

        if ($newPrice === null) {
            return PriceDecision::none('no_new_price');
        }

        $reference = $subscribedPrice ?? $oldPrice;
        if ($useCompareAt && $newCompareAt !== null && $newCompareAt > $newPrice) {
            $reference = $reference ?? $newCompareAt;
        }

        if ($reference === null || $reference <= 0) {
            return PriceDecision::none('no_reference');
        }

        if ($newPrice >= $reference) {
            return PriceDecision::none('not_a_drop');
        }

        $abs = round($reference - $newPrice, 2);
        $pct = round(($abs / $reference) * 100, 2);

        if ($abs < $minAbs) {
            return PriceDecision::none("below_abs_threshold:{$abs}<{$minAbs}");
        }
        if ($pct < $minPct) {
            return PriceDecision::none("below_pct_threshold:{$pct}<{$minPct}");
        }

        return new PriceDecision(
            eligible: true,
            reason: "drop:{$abs}({$pct}%)",
            absoluteDrop: $abs,
            percentDrop: $pct,
            newPrice: $newPrice,
            oldPrice: $reference,
        );
    }
}
