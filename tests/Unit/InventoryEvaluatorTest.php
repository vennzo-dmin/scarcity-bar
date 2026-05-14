<?php

namespace Tests\Unit;

use App\Domain\Inventory\InventoryEvaluator;
use App\Models\BisShopSetting;
use PHPUnit\Framework\TestCase;

class InventoryEvaluatorTest extends TestCase
{
    private function settings(array $inv = []): BisShopSetting
    {
        $s = new BisShopSetting();
        $s->inventory = array_merge(
            ['rule' => 'any_location', 'min_threshold' => 1, 'ignore_locations' => []],
            $inv
        );
        return $s;
    }

    private function variant(bool $availableForSale, array $levels, int $reported = 0, bool $tracked = true): array
    {
        $edges = array_map(fn($l) => ['node' => [
            'location'   => ['id' => 'gid://shopify/Location/' . $l['loc'], 'name' => $l['loc']],
            'quantities' => [['name' => 'available', 'quantity' => $l['qty']]],
        ]], $levels);

        return [
            'id'                => 'gid://shopify/ProductVariant/1',
            'availableForSale'  => $availableForSale,
            'inventoryQuantity' => $reported,
            'inventoryItem'     => [
                'tracked'         => $tracked,
                'inventoryLevels' => ['edges' => $edges],
            ],
        ];
    }

    public function test_eligible_when_any_location_has_stock(): void
    {
        $e = new InventoryEvaluator($this->settings());
        $d = $e->evaluateVariant($this->variant(true, [['loc' => 'A', 'qty' => 5], ['loc' => 'B', 'qty' => 0]]));
        $this->assertTrue($d->eligible);
        $this->assertSame(5, $d->total);
    }

    public function test_ineligible_when_below_threshold(): void
    {
        $e = new InventoryEvaluator($this->settings(['min_threshold' => 10]));
        $d = $e->evaluateVariant($this->variant(true, [['loc' => 'A', 'qty' => 5]]));
        $this->assertFalse($d->eligible);
    }

    public function test_ignores_excluded_locations(): void
    {
        $e = new InventoryEvaluator($this->settings(['ignore_locations' => ['gid://shopify/Location/A']]));
        $d = $e->evaluateVariant($this->variant(true, [['loc' => 'A', 'qty' => 100]]));
        $this->assertFalse($d->eligible);
    }

    public function test_untracked_inventory_follows_available_for_sale(): void
    {
        $e = new InventoryEvaluator($this->settings());
        $d = $e->evaluateVariant($this->variant(true, [], 0, false));
        $this->assertTrue($d->eligible);
    }
}
