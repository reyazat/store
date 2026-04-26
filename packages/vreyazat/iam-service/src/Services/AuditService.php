<?php

namespace IamApi\Services;

use IamApi\Repositories\Contracts\ActivityLogRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Spatie\Activitylog\Models\Activity;

class AuditService
{
    protected ActivityLogRepositoryInterface $activityLogRepository;

    public function __construct(ActivityLogRepositoryInterface $activityLogRepository)
    {
        $this->activityLogRepository = $activityLogRepository;
    }

    /**
     * Get activity log by ID
     */
    public function getActivityLog(int $id): ?Activity
    {
        return $this->activityLogRepository->findById($id);
    }

    /**
     * Get paginated activity logs with filters
     */
    public function getActivityLogs(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->activityLogRepository->getPaginated($filters, $perPage);
    }

    /**
     * Get activity logs for a specific user
     */
    public function getUserActivityLogs(int $userId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->activityLogRepository->findByUser($userId, $perPage);
    }

    /**
     * Get activity logs for a specific organization
     */
    public function getOrganizationActivityLogs(int $organizationId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->activityLogRepository->findByOrganization($organizationId, $perPage);
    }

    /**
     * Clean up old activity logs
     */
    public function cleanupOldLogs(int $days = 365): int
    {
        return $this->activityLogRepository->deleteOlderThan($days);
    }

    /**
     * Format activity log for display
     */
    public function formatActivityLog(Activity $activity): array
    {
        return [
            'id' => $activity->id,
            'log_name' => $activity->log_name,
            'description' => $activity->description,
            'event' => $activity->event ?? null,
            'subject' => $activity->subject ? [
                'type' => $activity->subject_type,
                'id' => $activity->subject_id,
                'data' => $this->getSubjectData($activity->subject),
            ] : null,
            'causer' => $activity->causer ? [
                'type' => $activity->causer_type,
                'id' => $activity->causer_id,
                'name' => $activity->causer->name ?? null,
                'email' => $activity->causer->email ?? null,
            ] : null,
            'properties' => $activity->properties ?? [],
            'created_at' => $activity->created_at,
            'updated_at' => $activity->updated_at,
        ];
    }

    /**
     * Get subject data based on type
     */
    protected function getSubjectData($subject): ?array
    {
        if (!$subject) {
            return null;
        }

        $data = [
            'id' => $subject->id ?? null,
        ];

        // Add type-specific data
        if (method_exists($subject, 'name')) {
            $data['name'] = $subject->name;
        }

        if (method_exists($subject, 'email')) {
            $data['email'] = $subject->email;
        }

        return $data;
    }
}
