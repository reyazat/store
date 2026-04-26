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
 * @property User user
 * @property string code_hash
 * @property Carbon used_at
 * @property Carbon created_at
 */
class UserRecoveryCode extends Model
{
    protected $fillable = ['user_id','code_hash','used_at'];
    protected $casts = ['used_at'=>'datetime'];

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

    public function markUsed(): void {
        $this->update(['used_at'=>now()]);
    }
}
