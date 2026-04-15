<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $option_id
 * @property int $product_id
 * @property int $required
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read Option $option
 * @property-read Product $product
 */
class OptionProduct extends AbstractModel
{
    protected bool $usesGlobalScopes = false;

    protected $table = 'option_product';

    protected $fillable = [
        'option_id',
        'product_id',
        'required',
    ];

    public array $filterable = [
        'option_id',
        'product_id',
        'required',
    ];

    public array $sortable = [
        'option_id',
        'product_id',
    ];

    protected $casts = [
        'option_id' => 'integer',
        'product_id' => 'integer',
        'required' => 'integer',
    ];

    public function option(): BelongsTo
    {
        return $this->belongsTo(Option::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
