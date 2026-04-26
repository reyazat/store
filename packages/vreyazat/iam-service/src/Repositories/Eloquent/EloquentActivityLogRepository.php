<?php

namespace IamApi\Repositories\Eloquent;

use IamApi\Repositories\Contracts\ActivityLogRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Spatie\Activitylog\Models\Activity;

class EloquentActivityLogRepository implements ActivityLogRepositoryInterface
{
    protected Activity $model;

    public function __construct(Activity $model)
    {
        $this->model = $model;
    }

    /**
     * Find activity log by ID
     */
    public function findById(int $id): ?Activity
    {
        return $this->model->query()->with(['causer', 'subject'])->find($id);
    }

    /**
     * Get paginated activity logs with filters
     */
    public function getPaginated(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->model->query()->with(['causer', 'subject'])
            ->orderBy('created_at', 'desc');

        // Filter by user (causer) - support both UUID and ID for backward compatibility
        if (!empty($filters['user_uuid'])) {
            // Find user by UUID first
            $user = \IamApi\Models\User::where('uuid', $filters['user_uuid'])->first();
            if ($user) {
                $query->where('causer_id', $user->id)
                      ->where('causer_type', 'IamApi\\Models\\User');
            }
        } elseif (!empty($filters['user_id'])) {
            // Fallback to ID for backward compatibility
            $query->where('causer_id', $filters['user_id'])
                  ->where('causer_type', 'IamApi\\Models\\User');
        }

        // Filter by organization
        if (!empty($filters['organization_id'])) {
            $query->whereJsonContains('properties->organization_id', (int)$filters['organization_id']);
        }

        // Filter by log name (event type)
        if (!empty($filters['log_name'])) {
            $query->where('log_name', $filters['log_name']);
        }

        // Filter by event (description)
        if (!empty($filters['event'])) {
            $query->where('description', 'like', '%' . $filters['event'] . '%');
        }

        // Filter by IP address
        if (!empty($filters['ip'])) {
            $query->whereJsonContains('properties->ip', $filters['ip']);
        }

        // Filter by date range
        if (!empty($filters['start_date'])) {
            $query->whereDate('created_at', '>=', $filters['start_date']);
        }

        if (!empty($filters['end_date'])) {
            $query->whereDate('created_at', '<=', $filters['end_date']);
        }

        // Filter by subject type
        if (!empty($filters['subject_type'])) {
            $query->where('subject_type', $filters['subject_type']);
        }

        // Filter by subject ID
        if (!empty($filters['subject_id'])) {
            $query->where('subject_id', $filters['subject_id']);
        }

        return $query->paginate($perPage);
    }

    /**
     * Get activity logs for a specific user
     */
    public function findByUser(int $userId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->query()->with(['causer', 'subject'])
            ->where('causer_id', $userId)
            ->where('causer_type', 'App\\Models\\User')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Get activity logs for a specific organization
     */
    public function findByOrganization(int $organizationId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->query()->with(['causer', 'subject'])
            ->whereJsonContains('properties->organization_id', $organizationId)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Get activity logs by event name
     */
    public function findByEvent(string $event, int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->query()->with(['causer', 'subject'])
            ->where('description', 'like', '%' . $event . '%')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Get activity logs by IP address
     */
    public function findByIp(string $ip, int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->query()->with(['causer', 'subject'])
            ->whereJsonContains('properties->ip', $ip)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Get activity logs by date range
     */
    public function findByDateRange(string $startDate, string $endDate, int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->query()->with(['causer', 'subject'])
            ->whereDate('created_at', '>=', $startDate)
            ->whereDate('created_at', '<=', $endDate)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Delete old activity logs
     */
    public function deleteOlderThan(int $days): int
    {
        $date = now()->subDays($days);
        return $this->model->query()->where('created_at', '<', $date)->delete();
    }
}
