<?php

namespace App\Models;

use App\Scopes\FilterScope;
use App\Scopes\LimitOffsetScope;
use App\Scopes\OrderScope;
use App\Scopes\WithScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

abstract class AbstractModel extends Model
{
    /**
     * Enable global scopes for filtering, sorting, etc.
     */
    protected bool $usesGlobalScopes = true;
    /**
     * Fields that can be filtered via query string.
     * Example: name$eq=John&age$gte=18
     *
     * @var array
     */
    public array $filterable = [];

    /**
     * Fields that can be sorted via query string.
     * Example:sort=name,-created_at
     *
     * @var array
     */
    public array $sortable = [];

    /**
     * The "booting" method of the model.
     * Applies global scopes for filtering, sorting, pagination, and eager loading.
     *
     * @return void
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Model $model) {
            if (
                empty($model->uuid) &&
                \Illuminate\Support\Facades\Schema::hasColumn($model->getTable(), 'uuid')
            ) {
                $model->uuid = (string) Str::uuid();
            }
        });

        if (static::shouldUseGlobalScopes()) {
            static::addGlobalScope(new FilterScope());
            static::addGlobalScope(new OrderScope());
            static::addGlobalScope(new LimitOffsetScope());
            static::addGlobalScope(new WithScope());
        }
    }

    protected static function shouldUseGlobalScopes(): bool
    {
        return (new static())->usesGlobalScopes;
    }

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName(): string
    {
        return parent::getRouteKeyName();
    }
}
