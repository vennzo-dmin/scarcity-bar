<?php

namespace Tests\Unit;

use App\Domain\Pricing\PriceChangeEvaluator;
use App\Models\BisShopSetting;
use PHPUnit\Framework\TestCase;

class PriceChangeEvaluatorTest extends TestCase
{
    private function evaluator(array $pricing = []): PriceChangeEvaluator
    {
        $s = new BisShopSetting();
        $s->pricing = array_merge(
            ['min_abs_drop' => 0.50, 'min_pct_drop' => 5, 'use_compare_at' => true],
            $pricing
        );
        return new PriceChangeEvaluator($s);
    }

    public function test_detects_valid_price_drop(): void
    {
        $d = $this->evaluator()->evaluate(100.00, null, 80.00, null);
        $this->assertTrue($d->eligible);
        $this->assertSame(20.0, $d->absoluteDrop);
        $this->assertSame(20.0, $d->percentDrop);
    }

    public function test_ignores_small_drop_below_absolute(): void
    {
        $d = $this->evaluator(['min_abs_drop' => 5])->evaluate(20.00, null, 19.50, null);
        $this->assertFalse($d->eligible);
    }

    public function test_ignores_small_drop_below_percent(): void
    {
        $d = $this->evaluator(['min_pct_drop' => 10])->evaluate(100.00, null, 97.00, null);
        $this->assertFalse($d->eligible);
    }

    public function test_price_increase_is_not_a_drop(): void
    {
        $d = $this->evaluator()->evaluate(80.00, null, 100.00, null);
        $this->assertFalse($d->eligible);
    }

    public function test_subscribed_price_reference_wins_over_old_price(): void
    {
        $d = $this->evaluator()->evaluate(oldPrice: 50.00, oldCompareAt: null, newPrice: 60.00, newCompareAt: null, subscribedPrice: 100.00);
        $this->assertTrue($d->eligible);
    }
}
