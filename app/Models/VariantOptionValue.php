<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $variant_id
 * @property int $option_id
 * @property int $option_value_id
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read ProductVariant $variant
 * @property-read Option $option
 * @property-read OptionValue $optionValue
 */
class VariantOptionValue extends AbstractModel
{
    protected bool $usesGlobalScopes = false;

    protected $table = 'variant_option_values';

    public $incrementing = false;

    protected $primaryKey;

    protected $fillable = [
        'variant_id',
        'option_id',
        'option_value_id',
    ];

    public array $filterable = [
        'variant_id',
        'option_id',
        'option_value_id',
    ];

    public array $sortable = [
        'variant_id',
        'option_id',
        'option_value_id',
    ];

    protected $casts = [
        'variant_id' => 'integer',
        'option_id' => 'integer',
        'option_value_id' => 'integer',
    ];

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id');
    }

    public function option(): BelongsTo
    {
        return $this->belongsTo(Option::class);
    }

    public function optionValue(): BelongsTo
    {
        return $this->belongsTo(OptionValue::class, 'option_value_id');
    }
}
