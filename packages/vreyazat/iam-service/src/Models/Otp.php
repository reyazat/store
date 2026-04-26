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
 * @property string purpose
 * @property string channel
 * @property string code_hash
 * @property Carbon expires_at
 * @property int attempts
 * @property int max_attempts
 * @property boolean used
 */
class Otp extends Model
{
    protected $fillable = [
        'user_id','purpose','channel','code_hash','expires_at','attempts','max_attempts','used'
    ];
    protected $casts = [
        'expires_at' => 'datetime',
        'used'       => 'bool',
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

    public function scopeActive($q){
        return $q->where('used',false)->where('expires_at','>',now());
    }

    public function isExpired(): bool { return now()->gte($this->expires_at); }
}
