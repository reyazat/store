<?php

namespace IamApi\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * @property int id
 * @property string uuid
 * @property int user_id
 * @property string jti
 * @property string refresh_token_hash
 * @property string user_agent
 * @property string ip
 * @property string device_name
 * @property Carbon revoked_at
 * @property Carbon expires_at
 * @property Carbon last_used_at
 */
class JwtToken extends Model
{
    protected $fillable = [
        'user_id', 'jti', 'refresh_token_hash', 'user_agent', 'ip', 'device_name',
        'last_used_at', 'expires_at', 'revoked_at'
    ];
    protected $casts = [
        'last_used_at' => 'datetime',
        'expires_at' => 'datetime',
        'revoked_at' => 'datetime',
    ];

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function scopeActive($q)
    {
        return $q->whereNull('revoked_at')->where('expires_at', '>', now());
    }

    public function revoke(): void
    {
        $this->update(['revoked_at' => now()]);
    }
}
