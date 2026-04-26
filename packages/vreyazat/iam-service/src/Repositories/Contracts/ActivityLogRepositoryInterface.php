<?php

namespace IamApi\Repositories\Contracts;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Spatie\Activitylog\Models\Activity;

interface ActivityLogRepositoryInterface
{
    /**
     * Find activity log by ID
     */
    public function findById(int $id): ?Activity;

    /**
     * Get paginated activity logs with filters
     */
    public function getPaginated(array $filters = [], int $perPage = 15): LengthAwarePaginator;

    /**
     * Get activity logs for a specific user
     */
    public function findByUser(int $userId, int $perPage = 15): LengthAwarePaginator;

    /**
     * Get activity logs for a specific organization
     */
    public function findByOrganization(int $organizationId, int $perPage = 15): LengthAwarePaginator;

    /**
     * Get activity logs by event name
     */
    public function findByEvent(string $event, int $perPage = 15): LengthAwarePaginator;

    /**
     * Get activity logs by IP address
     */
    public function findByIp(string $ip, int $perPage = 15): LengthAwarePaginator;

    /**
     * Get activity logs by date range
     */
    public function findByDateRange(string $startDate, string $endDate, int $perPage = 15): LengthAwarePaginator;

    /**
     * Delete old activity logs
     */
    public function deleteOlderThan(int $days): int;
}
