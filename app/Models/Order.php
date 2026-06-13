<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

class Order extends AbstractModel
{
    use SoftDeletes;

    protected bool $usesGlobalScopes = false;

    protected $fillable = [
        'order_number',
        'user_id',
        'address_id',
        'status',
        'payment_status',
        'payment_method',
        'subtotal',
        'tax',
        'shipping',
        'discount',
        'total',
        'notes',
        'admin_notes',
        'shipping_code',
        'paid_at',
        'shipped_at',
        'delivered_at',
        'cancelled_at',
    ];

    protected $casts = [
        'subtotal' => 'decimal:4',
        'tax' => 'decimal:4',
        'shipping' => 'decimal:4',
        'discount' => 'decimal:4',
        'total' => 'decimal:4',
        'paid_at' => 'datetime',
        'shipped_at' => 'datetime',
        'delivered_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function address(): BelongsTo
    {
        return $this->belongsTo(Address::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function orderAddress(): HasOne
    {
        return $this->hasOne(OrderAddress::class);
    }

    public function paymentTransactions(): HasMany
    {
        return $this->hasMany(PaymentTransaction::class);
    }

    public function latestTransaction(): HasOne
    {
        return $this->hasOne(PaymentTransaction::class)->latestOfMany();
    }

    public function histories(): HasMany
    {
        return $this->hasMany(OrderHistory::class)->orderBy('created_at', 'asc');
    }

    /**
     * Record a history entry for this order.
     */
    public function recordHistory(string $event, ?string $fromStatus = null, ?string $toStatus = null, ?string $note = null): void
    {
        $this->histories()->create([
            'user_id'     => Auth::id(),
            'event'       => $event,
            'from_status' => $fromStatus,
            'to_status'   => $toStatus,
            'note'        => $note,
        ]);
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($order) {
            if (!$order->order_number) {
                $order->order_number = 'ORD-' . strtoupper(uniqid());
            }
        });
    }
}
