<?php

namespace IamApi\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * @property int id
 * @property string uuid
 * @property int organization_id
 * @property Organization organization
 * @property string email
 * @property string role
 * @property string token
 * @property int invited_by
 * @property string status
 * @property Carbon expires_at
 * @property Carbon accepted_at
 * @property Carbon rejected_at
 * @property Carbon cancelled_at
 * @property int accepted_by
 */
class OrganizationInvitation extends Model
{
    protected $fillable = [
        'organization_id', 'email', 'role', 'token', 'invited_by', 'status',
        'expires_at', 'accepted_at', 'rejected_at', 'cancelled_at', 'accepted_by'
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'accepted_at' => 'datetime',
        'rejected_at' => 'datetime',
        'cancelled_at' => 'datetime',
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

    public function organization(): BelongsTo
    { return $this->belongsTo(Organization::class, 'organization_id', 'id'); }

    public function invitedBy(): BelongsTo
    { return $this->belongsTo(User::class, 'invited_by', 'id'); }

    public function acceptedBy(): BelongsTo
    { return $this->belongsTo(User::class, 'accepted_by', 'id'); }

    public function scopeValid($q){
        return $q->where('status', 'pending')->where(function($qq){
            $qq->whereNull('expires_at')->orWhere('expires_at', '>', now());
        });
    }

    public function scopePending($q){
        return $q->where('status', 'pending');
    }

    public function scopeExpired($q){
        return $q->where('expires_at', '<', now())->where('status', 'pending');
    }
}
