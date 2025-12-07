<?php

namespace Laravilt\Auth\Models;

use Illuminate\Database\Eloquent\Model;

class TwoFactorCode extends Model
{
    protected $fillable = [
        'user_id',
        'phone',
        'email',
        'code',
        'expires_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    /**
     * Check if the code is expired.
     */
    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }
}
