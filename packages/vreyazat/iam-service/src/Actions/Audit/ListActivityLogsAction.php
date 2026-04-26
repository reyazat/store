<?php

namespace IamApi\Actions\Audit;

use IamApi\Actions\BaseAction;
use IamApi\Services\AuditService;
use Exception;

class ListActivityLogsAction extends BaseAction
{
    public function __construct(
        protected AuditService $auditService
    ) {}

    public function execute(array $data = []): array
    {
        try {
            // In single-organization model, use user's organization_id
            $user = $data['user'] ?? auth()->user();
            $organizationId = $user?->organization_id;

            $filters = [
                'user_uuid' => $data['user_uuid'] ?? null,
                'organization_id' => $organizationId, // Use user's organization, not from request
                'log_name' => $data['log_name'] ?? null,
                'event' => $data['event'] ?? null,
                'ip' => $data['ip'] ?? null,
                'start_date' => $data['start_date'] ?? null,
                'end_date' => $data['end_date'] ?? null,
                'subject_type' => $data['subject_type'] ?? null,
                'subject_id' => $data['subject_id'] ?? null,
            ];

            // Remove null values
            $filters = array_filter($filters, static fn($value) => $value !== null);

            $perPage = $data['per_page'] ?? 15;
            $activityLogs = $this->auditService->getActivityLogs($filters, $perPage);

            // Format logs from the collection
            $formattedLogs = $activityLogs->getCollection()->map(function ($log) {
                return $this->auditService->formatActivityLog($log);
            });

            return $this->success([
                'logs' => $formattedLogs,
                'pagination' => [
                    'current_page' => $activityLogs->currentPage(),
                    'last_page' => $activityLogs->lastPage(),
                    'per_page' => $activityLogs->perPage(),
                    'total' => $activityLogs->total(),
                    'from' => $activityLogs->firstItem(),
                    'to' => $activityLogs->lastItem(),
                ]
            ]);

        } catch (Exception $e) {
            return $this->error('Failed to fetch activity logs: ' . $e->getMessage(), null, 500);
        }
    }
}
