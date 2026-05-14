<?php

namespace App\Domain\Inventory;

final class InventoryDecision
{
    public function __construct(
        public readonly bool $eligible,
        public readonly string $reason,
        public readonly int $total,
        public readonly int $sellable,
    ) {}
}
