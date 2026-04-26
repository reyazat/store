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
 * @property string type
 * @property string token
 * @property bool used
 * @property Carbon expires_at
 */
class VerificationToken extends Model
{
    protected $fillable = ['user_id', 'type', 'token', 'used', 'expires_at'];
    
    protected $casts = [
        'used' => 'boolean',
        'expires_at' => 'datetime',
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
    { return $this->belongsTo(User::class, 'user_id', 'id'); }

    public function scopeValid($q){
        return $q->where('used', false)->where(function($qq){
            $qq->whereNull('expires_at')->orWhere('expires_at', '>', now());
        });
    }

    public function scopeExpired($q){
        return $q->where('expires_at', '<', now());
    }

    public function scopeByType($q, string $type){
        return $q->where('type', $type);
    }

    public function isExpired(): bool
    {
        return $this->expires_at && now()->gt($this->expires_at);
    }

    public function isUsed(): bool
    {
        return $this->used;
    }

    public function isValid(): bool
    {
        return !$this->isUsed() && !$this->isExpired();
    }
}
