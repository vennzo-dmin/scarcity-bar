<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BisProductSnapshot extends Model
{
    protected $table = 'bis_product_snapshots';

    protected $fillable = [
        'user_id', 'product_id', 'variant_id', 'inventory_item_id',
        'inventory_total', 'inventory_sellable',
        'price', 'compare_at_price', 'currency',
        'available', 'checked_at',
    ];

    protected $casts = [
        'available'        => 'boolean',
        'checked_at'       => 'datetime',
        'price'            => 'decimal:2',
        'compare_at_price' => 'decimal:2',
    ];
}
