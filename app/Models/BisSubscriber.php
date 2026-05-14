<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class BisSubscriber extends Model
{
    protected $table = 'bis_subscribers';

    protected $fillable = [
        'user_id', 'email', 'phone', 'locale', 'shopify_customer_id',
        'consent_email', 'consent_sms',
        'email_unsubscribed_at', 'sms_unsubscribed_at',
        'unsubscribe_token', 'tags',
    ];

    protected $casts = [
        'consent_email'         => 'boolean',
        'consent_sms'           => 'boolean',
        'email_unsubscribed_at' => 'datetime',
        'sms_unsubscribed_at'   => 'datetime',
        'tags'                  => 'array',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $m) {
            if (empty($m->unsubscribe_token)) {
                $m->unsubscribe_token = Str::random(48);
            }
        });
    }

    public function alertSubscriptions(): HasMany
    {
        return $this->hasMany(BisAlertSubscription::class, 'subscriber_id');
    }

    public function canReceive(string $channel): bool
    {
        return match ($channel) {
            'email' => !empty($this->email) && $this->consent_email && !$this->email_unsubscribed_at,
            'sms'   => !empty($this->phone) && $this->consent_sms && !$this->sms_unsubscribed_at,
            default => false,
        };
    }
}
