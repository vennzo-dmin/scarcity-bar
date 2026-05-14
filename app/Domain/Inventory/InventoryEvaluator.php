<?php

namespace App\Domain\Inventory;

use App\Models\BisShopSetting;

class InventoryEvaluator
{
    public function __construct(private readonly BisShopSetting $settings) {}

    /**
     * Evaluate a variant shape returned by ProductReader.
     * Returns an InventoryDecision describing whether this variant is eligible
     * to trigger a back-in-stock send.
     */
    public function evaluateVariant(array $variant): InventoryDecision
    {
        $rule         = (string)($this->settings->inventory['rule'] ?? 'any_location');
        $minThreshold = (int)($this->settings->inventory['min_threshold'] ?? 1);
        $ignoreLocs   = (array)($this->settings->inventory['ignore_locations'] ?? []);

        $totalAvailable    = 0;
        $sellableAvailable = 0;

        $invItem = $variant['inventoryItem'] ?? null;
        $tracked = (bool)($invItem['tracked'] ?? true);
        $levels  = $invItem['inventoryLevels']['edges'] ?? [];

        foreach ($levels as $edge) {
            $loc = $edge['node']['location'] ?? [];
            $locGid = $loc['id'] ?? null;
            if ($locGid && in_array($locGid, $ignoreLocs, true)) continue;

            $qtys = $edge['node']['quantities'] ?? [];
            $avail = 0;
            foreach ($qtys as $q) {
                if (($q['name'] ?? null) === 'available') {
                    $avail = (int)($q['quantity'] ?? 0);
                    break;
                }
            }
            $totalAvailable    += max(0, $avail);
            $sellableAvailable += max(0, $avail);
        }

        $availableForSale = (bool)($variant['availableForSale'] ?? false);
        $reportedQty      = (int)($variant['inventoryQuantity'] ?? 0);

        if (!$tracked) {
            return new InventoryDecision(
                eligible: $availableForSale,
                reason: $availableForSale ? 'untracked_available' : 'untracked_unavailable',
                total: $reportedQty,
                sellable: $reportedQty,
            );
        }

        $qty = match ($rule) {
            'sellable', 'online_only' => $sellableAvailable,
            default                   => $totalAvailable,
        };

        $eligible = $qty >= $minThreshold && $availableForSale;

        return new InventoryDecision(
            eligible: $eligible,
            reason: $eligible ? "eligible:{$rule}:{$qty}" : "ineligible:{$rule}:{$qty}<{$minThreshold}",
            total: $totalAvailable,
            sellable: $sellableAvailable,
        );
    }
}
