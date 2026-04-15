<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $listing_id
 * @property int $customer_group_id
 * @property float $price
 * @property int $min_qty
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read Listing $listing
 * @property-read CustomerGroup $customerGroup
 */
class ListingCustomerGroupPrice extends AbstractModel
{
    protected bool $usesGlobalScopes = false;

    protected $table = 'listing_customer_group_prices';

    public $incrementing = false;

    protected $primaryKey;

    protected $fillable = [
        'listing_id',
        'customer_group_id',
        'price',
        'min_qty',
    ];

    public array $filterable = [
        'listing_id',
        'customer_group_id',
        'price',
        'created_at',
    ];

    public array $sortable = [
        'listing_id',
        'price',
        'min_qty',
        'created_at',
    ];

    protected $casts = [
        'listing_id' => 'integer',
        'customer_group_id' => 'integer',
        'price' => 'decimal:4',
        'min_qty' => 'integer',
    ];

    public function listing(): BelongsTo
    {
        return $this->belongsTo(Listing::class);
    }

    public function customerGroup(): BelongsTo
    {
        return $this->belongsTo(CustomerGroup::class);
    }
}
