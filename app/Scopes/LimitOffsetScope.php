<?php

namespace App\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class LimitOffsetScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     *
     * Usage:limit=10&offset=20
     * Or: per_page=10&page=3
     *
     * @param Builder $builder
     * @param Model $model
     * @return void
     */
    public function apply(Builder $builder, Model $model): void
    {
        // Do not apply to eager-loaded relation queries (they use a whereIn binding)
        $wheres = $builder->getQuery()->wheres ?? [];
        foreach ($wheres as $where) {
            if (($where['type'] ?? '') === 'InRaw' || ($where['type'] ?? '') === 'In') {
                return;
            }
        }

        $limit = request()->query('limit');
        $offset = request()->query('offset');
        $perPage = request()->query('per_page');
        $page = request()->query('page');

        // Handle limit/offset style
        if ($limit !== null) {
            $limit = (int) $limit;

            // Apply max limit for security
            $maxLimit = config('app.max_query_limit', 100);
            $limit = min($limit, $maxLimit);

            $builder->limit($limit);
        }

        if ($offset !== null) {
            $builder->offset((int) $offset);
        }

        // Handle per_page/page style (alternative to limit/offset)
        if ($perPage !== null && $page !== null) {
            $perPage = (int) $perPage;
            $page = (int) $page;

            // Apply max limit for security
            $maxLimit = config('app.max_query_limit', 100);
            $perPage = min($perPage, $maxLimit);

            $offset = ($page - 1) * $perPage;

            $builder->limit($perPage)->offset($offset);
        }
    }
}
