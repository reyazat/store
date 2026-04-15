<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $product_id
 * @property string $file_name
 * @property int $sorted
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read Product $product
 */
class ProductImage extends AbstractModel
{
    public array $filterable = [
        'id',
        'product_id',
        'created_at',
    ];

    public array $sortable = [
        'id',
        'sorted',
        'created_at',
    ];

    protected $fillable = [
        'product_id',
        'file_name',
        'sorted',
    ];

    protected $casts = [
        'product_id' => 'integer',
        'sorted' => 'integer',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
