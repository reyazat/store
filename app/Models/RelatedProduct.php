<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $product_id
 * @property int $related_product_id
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read Product $product
 * @property-read Product $relatedProduct
 */
class RelatedProduct extends AbstractModel
{
    protected $table = 'related_products';

    public $incrementing = false;

    protected $primaryKey;

    protected $fillable = [
        'product_id',
        'related_product_id',
    ];

    public array $filterable = [
        'product_id',
        'related_product_id',
        'created_at',
    ];

    public array $sortable = [
        'product_id',
        'related_product_id',
        'created_at',
    ];

    protected $casts = [
        'product_id' => 'integer',
        'related_product_id' => 'integer',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function relatedProduct(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'related_product_id');
    }
}
