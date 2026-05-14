<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BisAlertSubscription extends Model
{
    public const TYPE_BACK_IN_STOCK = 'back_in_stock';
    public const TYPE_PRICE_DROP    = 'price_drop';

    public const STATUS_ACTIVE    = 'active';
    public const STATUS_QUEUED    = 'queued';
    public const STATUS_SENT      = 'sent';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_FAILED    = 'failed';

    protected $table = 'bis_alert_subscriptions';

    protected $fillable = [
        'user_id', 'subscriber_id', 'type', 'channel',
        'product_id', 'variant_id', 'product_handle',
        'product_title', 'variant_title', 'image_url',
        'price_at_subscription', 'currency',
        'status', 'notified_at', 'metadata',
    ];

    protected $casts = [
        'notified_at'           => 'datetime',
        'price_at_subscription' => 'decimal:2',
        'metadata'              => 'array',
    ];

    public function subscriber(): BelongsTo
    {
        return $this->belongsTo(BisSubscriber::class, 'subscriber_id');
    }
}
