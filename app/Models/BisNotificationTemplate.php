<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BisNotificationTemplate extends Model
{
    public const TYPE_BACK_IN_STOCK = 'back_in_stock';
    public const TYPE_PRICE_DROP    = 'price_drop';

    public const CHANNEL_EMAIL = 'email';
    public const CHANNEL_SMS   = 'sms';

    protected $table = 'bis_notification_templates';

    protected $fillable = [
        'user_id', 'type', 'channel', 'locale', 'subject', 'body', 'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
