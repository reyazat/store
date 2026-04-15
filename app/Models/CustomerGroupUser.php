<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $customer_group_id
 * @property int $user_id
 * @property-read CustomerGroup $customerGroup
 * @property-read User $user
 */
class CustomerGroupUser extends AbstractModel
{
    protected bool $usesGlobalScopes = false;

    protected $table = 'customer_group_user';

    public $timestamps = false;

    public $incrementing = false;

    protected $primaryKey;

    protected $fillable = [
        'customer_group_id',
        'user_id',
    ];

    public array $filterable = [
        'customer_group_id',
        'user_id',
    ];

    public array $sortable = [
        'customer_group_id',
        'user_id',
    ];

    public function customerGroup(): BelongsTo
    {
        return $this->belongsTo(CustomerGroup::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
