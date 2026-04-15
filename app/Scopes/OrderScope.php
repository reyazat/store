<?php

namespace App\Scopes;

use App\Models\AbstractModel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class OrderScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     *
     * Usage:sort=name,-created_at
     * - Prefix with '-' for descending order
     * - Multiple fields separated by comma
     *
     * @param Builder $builder
     * @param AbstractModel $model
     * @return void
     */
    public function apply(Builder $builder, Model $model): void
    {
        // Do not apply to eager-loaded relation queries
        $wheres = $builder->getQuery()->wheres ?? [];
        foreach ($wheres as $where) {
            if (($where['type'] ?? '') === 'InRaw' || ($where['type'] ?? '') === 'In') {
                return;
            }
        }

        $sortParam = request()->query('sort');

        if (!$sortParam) {
            return;
        }

        // Split multiple sort fields
        $sortFields = is_array($sortParam) ? $sortParam : explode(',', $sortParam);

        foreach ($sortFields as $sortField) {
            $sortField = trim($sortField);

            // Check for descending order (prefix with -)
            $direction = 'asc';
            if (str_starts_with($sortField, '-')) {
                $direction = 'desc';
                $sortField = substr($sortField, 1);
            }

            // Check if the field is sortable
            if (in_array($sortField, $model->sortable)) {
                $builder->orderBy($sortField, $direction);
            }
        }
    }
}
