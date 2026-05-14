<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BisAttributionEvent extends Model
{
    protected $table = 'bis_attribution_events';

    protected $fillable = [
        'user_id', 'alert_subscription_id', 'delivery_log_id',
        'subscriber_id', 'event', 'order_id', 'amount', 'currency', 'occurred_at',
    ];

    protected $casts = [
        'amount'      => 'decimal:2',
        'occurred_at' => 'datetime',
    ];
}
