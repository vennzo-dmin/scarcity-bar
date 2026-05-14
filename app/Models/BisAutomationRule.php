<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BisAutomationRule extends Model
{
    protected $table = 'bis_automation_rules';

    protected $fillable = [
        'user_id', 'name', 'type', 'is_active', 'config', 'last_run_at',
    ];

    protected $casts = [
        'is_active'   => 'boolean',
        'config'      => 'array',
        'last_run_at' => 'datetime',
    ];
}
