<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @property int $id
 * @property int $option_id
 * @property string $value
 * @property int $sorted
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read Option $option
 * @property-read Collection<int, ProductVariant> $variants
 */
class OptionValue extends AbstractModel
{
    public array $filterable = [
        'id',
        'option_id',
        'value',
        'created_at',
    ];

    public array $sortable = [
        'id',
        'sorted',
        'created_at',
    ];
    protected $fillable = [
        'option_id',
        'value',
        'sorted',
    ];

    protected $casts = [
        'option_id' => 'integer',
        'sorted' => 'integer',
    ];

    public function option(): BelongsTo
    {
        return $this->belongsTo(Option::class);
    }

    public function variants(): BelongsToMany
    {
        return $this->belongsToMany(ProductVariant::class, 'variant_option_values', 'option_value_id', 'variant_id')
            ->withPivot('option_id')
            ->withTimestamps();
    }
}
