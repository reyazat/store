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
 * @property string domain
 * @property Carbon verified_at
 */
class OrganizationDomain extends Model
{
    protected $fillable = ['organization_id','domain','verified_at'];
    protected $casts = ['verified_at'=>'datetime'];

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
}
