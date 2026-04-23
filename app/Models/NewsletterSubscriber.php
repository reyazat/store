<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NewsletterSubscriber extends AbstractModel
{
    protected bool $usesGlobalScopes = false;

    protected $fillable = [
        'email',
        'user_id',
        'source',
        'ip_address',
        'user_agent',
        'is_active',
        'confirmed_at',
        'unsubscribed_at',
    ];

    protected $casts = [
        'is_active'       => 'boolean',
        'confirmed_at'    => 'datetime',
        'unsubscribed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
