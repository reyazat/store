<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Address extends AbstractModel
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'title',
        'first_name',
        'last_name',
        'phone',
        'email',
        'country',
        'province',
        'city',
        'address',
        'postal_code',
        'notes',
        'is_default',
    ];

    protected $casts = [
        'is_default' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
