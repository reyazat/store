<?php

namespace IamApi\Http\Controllers\API;

use IamApi\Actions\Audit\GetActivityLogAction;
use IamApi\Actions\Audit\ListActivityLogsAction;
use IamApi\Http\Requests\Audit\ListActivityLogsRequest;
use IamApi\Http\Resources\AbstractResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

/**
 * @group Audit & Activity Logs
 *
 * APIs for viewing and managing activity logs and audit trails.
 * These endpoints allow tracking user actions and system events.
 */
class AuditController extends Controller
{
    public function __construct(
        protected ListActivityLogsAction $listAction,
        protected GetActivityLogAction $getAction
    ) {
        $this->middleware('auth:api');
    }

    /**
     * List activity logs
     *
     * Get a paginated list of activity logs with optional filters.
     * Requires audit.view permission.
     *
     * @authenticated
     *
     * @queryParam user_id integer Filter by user ID. Example: 1
     * @queryParam log_name string Filter by log name/category. Example: default
     * @queryParam event string Filter by event description. Example: login
     * @queryParam ip string Filter by IP address. Example: 192.168.1.1
     * @queryParam start_date string Filter by start date (YYYY-MM-DD). Example: 2025-01-01
     * @queryParam end_date string Filter by end date (YYYY-MM-DD). Example: 2025-12-31
     * @queryParam subject_type string Filter by subject type. Example: App\Models\User
     * @queryParam subject_id integer Filter by subject ID. Example: 1
     * @queryParam per_page integer Number of items per page (1-100). Example: 15
     *
     * @response 200 {
     *   "success": true,
     *   "message": null,
     *   "data": {
     *     "logs": [
     *       {
     *         "id": 1,
     *         "log_name": "default",
     *         "description": "User logged in",
     *         "event": "login",
     *         "subject": {
     *           "type": "App\\Models\\User",
     *           "id": 1,
     *           "data": {
     *             "id": 1,
     *             "name": "John Doe",
     *             "email": "john@example.com"
     *           }
     *         },
     *         "causer": {
     *           "type": "App\\Models\\User",
     *           "id": 1,
     *           "data": {
     *             "id": 1,
     *             "name": "John Doe",
     *             "email": "john@example.com"
     *           }
     *         },
     *         "properties": {
     *           "ip": "192.168.1.1",
     *           "user_agent": "Mozilla/5.0"
     *         },
     *         "created_at": "2025-10-01T08:00:00.000000Z",
     *         "updated_at": "2025-10-01T08:00:00.000000Z"
     *       }
     *     ],
     *     "pagination": {
     *       "current_page": 1,
     *       "last_page": 10,
     *       "per_page": 15,
     *       "total": 150,
     *       "from": 1,
     *       "to": 15
     *     }
     *   }
     * }
     */
    public function index(ListActivityLogsRequest $request): JsonResponse
    {
        $result = $this->listAction->execute($request->validated());

        if (!$result['success']) {
            return response()->json(
                AbstractResource::error($result['message'], $result['data'] ?? null),
                $result['code'] ?? 400
            );
        }

        return response()->json(
            AbstractResource::success($result['data'], $result['message'])
        );
    }

    /**
     * Get activity log details
     *
     * Get detailed information about a specific activity log entry.
     * Requires audit.view permission.
     *
     * @authenticated
     *
     * @urlParam id integer required The activity log ID. Example: 1
     *
     * @response 200 {
     *   "success": true,
     *   "message": null,
     *   "data": {
     *     "log": {
     *       "id": 1,
     *       "log_name": "default",
     *       "description": "User logged in",
     *       "event": "login",
     *       "subject": {
     *         "type": "App\\Models\\User",
     *         "id": 1,
     *         "data": {
     *           "id": 1,
     *           "name": "John Doe",
     *           "email": "john@example.com"
     *         }
     *       },
     *       "causer": {
     *         "type": "App\\Models\\User",
     *         "id": 1,
     *         "data": {
     *           "id": 1,
     *           "name": "John Doe",
     *           "email": "john@example.com"
     *         }
     *       },
     *       "properties": {
     *         "ip": "192.168.1.1",
     *         "user_agent": "Mozilla/5.0",
     *         "organization_id": 1
     *       },
     *       "created_at": "2025-10-01T08:00:00.000000Z",
     *       "updated_at": "2025-10-01T08:00:00.000000Z"
     *     }
     *   }
     * }
     */
    public function show(int $id): JsonResponse
    {
        $result = $this->getAction->execute(['log_id' => $id]);

        if (!$result['success']) {
            return response()->json(
                AbstractResource::error($result['message'], $result['data'] ?? null),
                $result['code'] ?? 400
            );
        }

        return response()->json(
            AbstractResource::success($result['data'], $result['message'])
        );
    }
}
