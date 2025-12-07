<?php

namespace Laravilt\Auth\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SocialAccount extends Model
{
    protected $fillable = [
        'user_id',
        'provider',
        'provider_id',
        'name',
        'email',
        'avatar',
        'token',
        'refresh_token',
        'expires_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    /**
     * Get the user that owns the social account.
     */
    public function user(): BelongsTo
    {
        // Get the user model from the default auth provider
        $defaultGuard = config('auth.defaults.guard', 'web');
        $provider = config("auth.guards.{$defaultGuard}.provider", 'users');
        $userModel = config("auth.providers.{$provider}.model", \App\Models\User::class);

        return $this->belongsTo($userModel);
    }
}
