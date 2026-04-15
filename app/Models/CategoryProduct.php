<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $category_id
 * @property int $product_id
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read Category $category
 * @property-read Product $product
 */
class CategoryProduct extends AbstractModel
{
    protected bool $usesGlobalScopes = false;

    protected $table = 'category_product';

    public $incrementing = false;

    protected $primaryKey;

    protected $fillable = [
        'category_id',
        'product_id',
    ];

    public array $filterable = [
        'category_id',
        'product_id',
    ];

    public array $sortable = [
        'category_id',
        'product_id',
    ];

    protected $casts = [
        'category_id' => 'integer',
        'product_id' => 'integer',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
