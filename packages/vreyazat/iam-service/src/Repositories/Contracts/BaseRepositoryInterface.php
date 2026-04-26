<?php

namespace IamApi\Repositories\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;

interface BaseRepositoryInterface
{
    /**
     * Find a model by its primary key (integer ID).
     * Note: For API usage, prefer findByUuid() method.
     */
    public function find(int $id): ?Model;

    /**
     * Find a model by its primary key or throw an exception (integer ID).
     * Note: For API usage, prefer findByUuidOrFail() method.
     */
    public function findOrFail(int $id): Model;

    /**
     * Find a model by its UUID.
     * This is the preferred method for API endpoints.
     */
    public function findByUuid(string $uuid): ?Model;

    /**
     * Find a model by its UUID or throw an exception.
     * This is the preferred method for API endpoints.
     */
    public function findByUuidOrFail(string $uuid): Model;

    /**
     * Get all models.
     */
    public function all(array $columns = ['*']): Collection;

    /**
     * Create a new model.
     */
    public function create(array $data): Model;

    /**
     * Update a model.
     */
    public function update(Model $model, array $data): bool;

    /**
     * Delete a model.
     */
    public function delete(Model $model): bool;

    /**
     * Delete a model by its primary key (integer ID).
     * Note: For API usage, prefer deleteByUuid() method.
     */
    public function deleteById(int $id): bool;

    /**
     * Delete a model by its UUID.
     * This is the preferred method for API endpoints.
     */
    public function deleteByUuid(string $uuid): bool;

    /**
     * Get paginated results.
     */
    public function paginate(int $perPage = 15, array $columns = ['*']): LengthAwarePaginator;

    /**
     * Find models by a specific field.
     */
    public function findBy(string $field, mixed $value, array $columns = ['*']): Collection;

    /**
     * Find the first model by a specific field.
     */
    public function findOneBy(string $field, mixed $value, array $columns = ['*']): ?Model;

    /**
     * Count all models.
     */
    public function count(): int;

    /**
     * Check if the model exists by field.
     */
    public function exists(string $field, mixed $value): bool;
}
