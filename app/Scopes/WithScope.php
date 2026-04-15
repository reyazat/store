<?php

namespace App\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class WithScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     *
     * Allows frontend to specify which relations to an eager load.
     * Usage:with=author, category, tags
     * Usage:with=author.profile, comments. User
     *
     * @param Builder $builder
     * @param Model $model
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

        $withParam = request()->query('with');

        if (!$withParam) {
            return;
        }

        // Split multiple relations
        $relations = is_array($withParam) ? $withParam : explode(',', $withParam);

        // Clean and validate: only allow alphanumeric, dots, and underscores
        // and only load relations that exist as public methods on the model
        $validRelations = [];
        foreach ($relations as $relation) {
            $relation = trim($relation);
            $rootRelation = explode('.', $relation)[0];

            if (
                preg_match('/^[a-zA-Z0-9_.]+$/', $relation) &&
                method_exists($model, $rootRelation)
            ) {
                $validRelations[] = $relation;
            }
        }

        if (!empty($validRelations)) {
            $builder->with($validRelations);
        }
    }
}
