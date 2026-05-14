<?php

namespace App\Domain\Pricing;

final class PriceDecision
{
    public function __construct(
        public readonly bool $eligible,
        public readonly string $reason,
        public readonly ?float $absoluteDrop = null,
        public readonly ?float $percentDrop = null,
        public readonly ?float $newPrice = null,
        public readonly ?float $oldPrice = null,
    ) {}

    public static function none(string $reason): self
    {
        return new self(false, $reason);
    }
}
