<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int    $id
 * @property int    $product_id
 * @property int    $user_id
 * @property int    $rating
 * @property string|null $title
 * @property string $body
 * @property string $status  pending|approved|rejected
 * @property-read Product $product
 * @property-read User    $user
 */
class ProductReview extends AbstractModel
{
    use SoftDeletes;

    protected bool $usesGlobalScopes = false;

    protected $fillable = [
        'product_id',
        'user_id',
        'rating',
        'title',
        'body',
        'status',
        'approved_at',
        'reply',
        'replied_at',
    ];

    protected $casts = [
        'rating'      => 'integer',
        'approved_at' => 'datetime',
        'replied_at'  => 'datetime',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('status', 'approved');
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending');
    }
}
