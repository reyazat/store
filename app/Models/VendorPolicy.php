<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $vendor_id
 * @property int|null $tax_class_id
 * @property string $return_policy
 * @property string $shipping_policy
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read Vendor $vendor
 * @property-read TaxClass|null $taxClass
 */
class VendorPolicy extends AbstractModel
{
    protected $primaryKey = 'vendor_id';

    public $incrementing = false;

    protected $fillable = [
        'vendor_id',
        'tax_class_id',
        'return_policy',
        'shipping_policy',
    ];

    public array $filterable = [
        'vendor_id',
        'tax_class_id',
        'created_at',
    ];

    public array $sortable = [
        'vendor_id',
        'created_at',
    ];

    protected $casts = [
        'vendor_id' => 'integer',
        'tax_class_id' => 'integer',
    ];

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function taxClass(): BelongsTo
    {
        return $this->belongsTo(TaxClass::class);
    }
}
