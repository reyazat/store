<?php

namespace App\Scopes;

use App\Models\AbstractModel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Str;

class FilterScope implements Scope
{
    /**
     * Supported filter operators.
     * Usage:field$operator=value
     * Example: ?price$gte=100&status$eq=published
     *
     * @var array
     */
    private array $operators = [
        'gt' => '>',
        'gte' => '>=',
        'eq' => '=',
        'neq' => '!=',
        'lt' => '<',
        'lte' => '<=',
        'like' => 'LIKE',
        'in' => 'IN',
        'nin' => 'NOT IN',
    ];

    /**
     * Apply the scope to a given Eloquent query builder.
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

        foreach (request()->query() as $item => $value) {
            // Parse field and operator from query string
            // Example: price$gte => field=price, operator=gte
            [$field, $operator] = array_pad(explode('$', $item, 2), 2, null);

            if (!$operator) {
                $operator = 'eq';
            }

            // Check if the field is filterable
            if (in_array($field, $model->filterable)) {
                // Check for custom filter method in a model
                $filterMethod = Str::camel($field) . 'Filter';

                if (method_exists($model, $filterMethod)) {
                    // Use custom filter method
                    $model->{$filterMethod}($builder, $value, $this->operators[$operator] ?? '=');
                } else {
                    // Apply standard filter
                    $this->applyStandardFilter($builder, $field, $operator, $value);
                }
            }
        }
    }

    /**
     * Apply standard filter based on operator.
     *
     * @param Builder $builder
     * @param string $field
     * @param string $operator
     * @param mixed $value
     * @return void
     */
    private function applyStandardFilter(Builder $builder, string $field, string $operator, mixed $value): void
    {
        $sqlOperator = $this->operators[$operator] ?? '=';

        match ($operator) {
            'like' => $builder->where($field, 'LIKE', "%$value%"),
            'in' => $builder->whereIn($field, is_array($value) ? $value : explode(',', $value)),
            'nin' => $builder->whereNotIn($field, is_array($value) ? $value : explode(',', $value)),
            default => $builder->where($field, $sqlOperator, $value),
        };
    }

}
