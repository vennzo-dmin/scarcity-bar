<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BisDeliveryLog extends Model
{
    public const STATUS_QUEUED  = 'queued';
    public const STATUS_SENT    = 'sent';
    public const STATUS_FAILED  = 'failed';
    public const STATUS_BOUNCED = 'bounced';
    public const STATUS_CLICKED = 'clicked';

    protected $table = 'bis_delivery_logs';

    protected $fillable = [
        'user_id', 'alert_subscription_id', 'subscriber_id',
        'type', 'channel', 'provider', 'recipient',
        'status', 'provider_message_id', 'error', 'sent_at',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
    ];
}
