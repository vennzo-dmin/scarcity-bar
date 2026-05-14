<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BisAbTest extends Model
{
    protected $table = 'bis_ab_tests';

    protected $fillable = [
        'user_id', 'name', 'target', 'status', 'variants', 'started_at', 'ended_at',
    ];

    protected $casts = [
        'variants'   => 'array',
        'started_at' => 'datetime',
        'ended_at'   => 'datetime',
    ];

    public function assignments(): HasMany
    {
        return $this->hasMany(BisAbAssignment::class, 'ab_test_id');
    }
}
