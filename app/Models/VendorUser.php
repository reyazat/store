<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $vendor_id
 * @property int $user_id
 * @property string $role
 * @property-read Vendor $vendor
 * @property-read User $user
 */
class VendorUser extends AbstractModel
{
    protected bool $usesGlobalScopes = false;

    protected $table = 'vendor_users';

    public $incrementing = false;

    protected $primaryKey;

    protected $fillable = [
        'vendor_id',
        'user_id',
        'role',
    ];

    public array $filterable = [
        'vendor_id',
        'user_id',
        'role',
    ];

    public array $sortable = [
        'vendor_id',
        'user_id',
    ];

    protected $casts = [
        'vendor_id' => 'integer',
        'user_id' => 'integer',
    ];

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
