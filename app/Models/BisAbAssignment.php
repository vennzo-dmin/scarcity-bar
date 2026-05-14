<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BisAbAssignment extends Model
{
    protected $table = 'bis_ab_assignments';

    protected $fillable = [
        'ab_test_id', 'subscriber_id', 'fingerprint',
        'variant_key', 'event', 'amount',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];
}
