<?php

namespace IamApi\Repositories\Contracts;

use Illuminate\Database\Eloquent\Model;

interface CrudRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Create or update a model.
     */
    public function createOrUpdate(array $data, array $conditions = []): Model;

    /**
     * Update or create a model by specific conditions.
     */
    public function updateOrCreate(array $conditions, array $data): Model;

    /**
     * Bulk insert data.
     */
    public function bulkInsert(array $data): bool;

    /**
     * Bulk update data.
     */
    public function bulkUpdate(array $data, string $key = 'id'): bool;

    /**
     * Softly delete a model (if supported).
     */
    public function softDelete(Model $model): bool;

    /**
     * Restore a soft deleted model (if supported).
     */
    public function restore(Model $model): bool;

    /**
     * Force delete a model.
     */
    public function forceDelete(Model $model): bool;
}
