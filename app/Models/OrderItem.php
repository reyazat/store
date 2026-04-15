<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends AbstractModel
{
    protected $fillable = [
        'order_id',
        'listing_id',
        'listing_variant_id',
        'product_id',
        'vendor_id',
        'product_name',
        'vendor_name',
        'sku',
        'quantity',
        'price',
        'tax',
        'subtotal',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'price' => 'decimal:4',
        'tax' => 'decimal:4',
        'subtotal' => 'decimal:4',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function listing(): BelongsTo
    {
        return $this->belongsTo(Listing::class);
    }

    public function listingVariant(): BelongsTo
    {
        return $this->belongsTo(ListingVariant::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }
}
