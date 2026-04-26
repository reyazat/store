<?php

namespace IamApi\Actions\Audit;

use IamApi\Actions\BaseAction;
use IamApi\Services\AuditService;
use Exception;

class GetActivityLogAction extends BaseAction
{
    public function __construct(
        protected AuditService $auditService
    ) {}

    public function execute(array $data = []): array
    {
        $logId = $data['log_id'];

        try {
            $activityLog = $this->auditService->getActivityLog($logId);

            if (!$activityLog) {
                return $this->error('Activity log not found', null, 404);
            }

            $formattedLog = $this->auditService->formatActivityLog($activityLog);

            return $this->success([
                'log' => $formattedLog
            ]);

        } catch (Exception $e) {
            return $this->error('Failed to fetch activity log: ' . $e->getMessage(), null, 500);
        }
    }
}
