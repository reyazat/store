<?php

namespace App\Helpers;

use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Helper class for UUID operations
 */
class UuidHelper
{
    /**
     * Convert UUID to internal ID for a given model
     *
     * @param string $modelClass
     * @param string|null $uuid
     * @param string $fieldName
     * @return int|null
     * @throws Exception
     */
    public static function uuidToId(string $modelClass, ?string $uuid, string $fieldName = ''): ?int
    {
        if (empty($uuid)) {
            return null;
        }

        // Validate UUID format
        if (!Str::isUuid($uuid)) {
            $field = $fieldName ?: 'UUID';
            throw new Exception("Invalid UUID format for {$field}");
        }

        // Check if model class exists
        if (!class_exists($modelClass)) {
            throw new Exception("Model class {$modelClass} not found");
        }

        /** @var Model $model */
        $model = $modelClass::query()->where('uuid', $uuid)->first();

        if (!$model) {
            $modelName = class_basename($modelClass);
            $field = $fieldName ?: $modelName;
            throw new Exception("{$field} not found with UUID: {$uuid}");
        }

        return $model->id;
    }

    /**
     * Convert UUID to ID with null safety
     *
     * @param string $modelClass
     * @param string|null $uuid
     * @return int|null
     */
    public static function safeUuidToId(string $modelClass, ?string $uuid): ?int
    {
        if (empty($uuid)) {
            return null;
        }

        if (!Str::isUuid($uuid)) {
            return null;
        }

        if (!class_exists($modelClass)) {
            return null;
        }

        /** @var Model|null $model */
        $model = $modelClass::query()->where('uuid', $uuid)->first();

        return $model?->id;
    }

    /**
     * Validate and convert multiple UUIDs to IDs
     *
     * @param Model $modelClass
     * @param array $uuids
     * @return array
     */
    public static function uuidsToIds(Model $modelClass, array $uuids): array
    {
        if (empty($uuids)) {
            return [];
        }

        // Filter valid UUIDs
        $validUuids = array_filter($uuids, function ($uuid) {
            return is_string($uuid) && Str::isUuid($uuid);
        });

        if (empty($validUuids)) {
            return [];
        }

        return $modelClass::query()
            ->whereIn('uuid', $validUuids)
            ->pluck('id')
            ->toArray();
    }

    /**
     * Check if a UUID exists in a model
     *
     * @param string $modelClass
     * @param string $uuid
     * @return bool
     */
    public static function uuidExists(string $modelClass, string $uuid): bool
    {
        if (!Str::isUuid($uuid)) {
            return false;
        }

        return $modelClass::query()->where('uuid', $uuid)->exists();
    }

    /**
     * Generate a new UUID
     *
     * @return string
     */
    public static function generate(): string
    {
        return (string) Str::uuid();
    }

    /**
     * Validate UUID format
     *
     * @param mixed $value
     * @return bool
     */
    public static function isValid($value): bool
    {
        return is_string($value) && Str::isUuid($value);
    }

    /**
     * Get model by UUID with optional relations
     *
     * @param string $modelClass
     * @param string $uuid
     * @param array $relations
     * @return Model|null
     */
    public static function findByUuid(string $modelClass, string $uuid, array $relations = []): ?Model
    {
        if (!Str::isUuid($uuid)) {
            return null;
        }

        $query = $modelClass::query()->where('uuid', $uuid);

        if (!empty($relations)) {
            $query->with($relations);
        }

        return $query->first();
    }
}
