<?php

namespace IamApi\Http\Controllers\API;

use IamApi\Models\Department;
use IamApi\Models\Organization;
use IamApi\Http\Resources\AbstractResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use IamApi\Rules\NoHtml;

/**
 * @group Department Management
 *
 * APIs for managing organization departments/sections.
 * Each organization can have multiple departments with members.
 */
class DepartmentController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api');
    }

    /**
     * List departments
     *
     * Get all departments in an organization.
     *
     * @authenticated
     *
     * @urlParam org integer required The organization ID. Example: 1
     *
     * @response 200 {
     *   "success": true,
     *   "message": null,
     *   "data": {
     *     "departments": [
     *       {
     *         "id": 1,
     *         "name": "Public Relations",
     *         "slug": "public-relations",
     *         "description": "PR Department",
     *         "status": "active",
     *         "members_count": 5,
     *         "created_at": "2025-01-01T00:00:00.000000Z",
     *         "updated_at": "2025-01-01T00:00:00.000000Z"
     *       }
     *     ]
     *   }
     * }
     */
    public function index(Organization $org): JsonResponse
    {
        // Verify user belongs to organization
        if (Auth::user()->organization_id !== $org->id && !Auth::user()->isSuperAdmin()) {
            return response()->json(
                AbstractResource::error('Unauthorized'),
                403
            );
        }

        $departments = $org->departments()
            ->with('users')
            ->active()
            ->get()
            ->map(fn($dept) => [
                'uuid' => $dept->uuid,
                'name' => $dept->name,
                'slug' => $dept->slug,
                'description' => $dept->description,
                'status' => $dept->status,
                'members_count' => $dept->users()->count(),
                'created_at' => $dept->created_at->toISOString(),
                'updated_at' => $dept->updated_at->toISOString(),
            ]);

        return response()->json(
            AbstractResource::success(['departments' => $departments])
        );
    }

    /**
     * Create department
     *
     * Create a new department in the organization.
     * Requires org.manage permission.
     *
     * @authenticated
     *
     * @urlParam org integer required The organization ID. Example: 1
     * @bodyParam name string required Department name. Example: Public Relations
     * @bodyParam description string Optional department description. Example: PR Department
     * @bodyParam parent_id integer Optional parent department ID for sub-departments. Example: null
     *
     * @response 201 {
     *   "success": true,
     *   "message": "Department created successfully",
     *   "data": {
     *     "department": {
     *       "id": 1,
     *       "name": "Public Relations",
     *       "slug": "public-relations",
     *       "description": "PR Department",
     *       "status": "active",
     *       "created_at": "2025-01-01T00:00:00.000000Z"
     *     }
     *   }
     * }
     */
    public function store(Request $request, Organization $org): JsonResponse
    {
        // Verify user has permission to manage organization
        if (!Auth::user()->can('org.manage', $org) && !Auth::user()->isSuperAdmin()) {
            return response()->json(
                AbstractResource::error('Unauthorized'),
                403
            );
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', new NoHtml()],
            'description' => ['nullable', 'string', 'max:1000', new NoHtml()],
            'parent_uuid' => ['nullable', 'string', 'uuid', 'exists:departments,uuid'],
        ]);

        // Verify parent department belongs to same organization
        $parentId = null;
        if ($validated['parent_uuid'] ?? null) {
            $parent = Department::where('uuid', $validated['parent_uuid'])->first();
            if (!$parent || $parent->organization_id !== $org->id) {
                return response()->json(
                    AbstractResource::error('Parent department must belong to the same organization'),
                    422
                );
            }
            $parentId = $parent->id;
        }

        $department = Department::create([
            'organization_id' => $org->id,
            'name' => $validated['name'],
            'slug' => Str::slug($validated['name']),
            'description' => $validated['description'] ?? null,
            'parent_id' => $parentId,
            'status' => 'active',
        ]);

        return response()->json(
            AbstractResource::success([
                'department' => [
                    'uuid' => $department->uuid,
                    'name' => $department->name,
                    'slug' => $department->slug,
                    'description' => $department->description,
                    'status' => $department->status,
                    'created_at' => $department->created_at->toISOString(),
                ]
            ], 'Department created successfully'),
            201
        );
    }

    /**
     * Get department details
     *
     * Get detailed information about a specific department.
     *
     * @authenticated
     *
     * @urlParam org integer required The organization ID. Example: 1
     * @urlParam department integer required The department ID. Example: 1
     *
     * @response 200 {
     *   "success": true,
     *   "message": null,
     *   "data": {
     *     "department": {
     *       "id": 1,
     *       "name": "Public Relations",
     *       "slug": "public-relations",
     *       "description": "PR Department",
     *       "status": "active",
     *       "members": [
     *         {
     *           "id": 1,
     *           "name": "John Doe",
     *           "email": "john@example.com",
     *           "role": "manager"
     *         }
     *       ],
     *       "created_at": "2025-01-01T00:00:00.000000Z",
     *       "updated_at": "2025-01-01T00:00:00.000000Z"
     *     }
     *   }
     * }
     */
    public function show(Organization $org, Department $department): JsonResponse
    {
        // Verify department belongs to organization
        if ($department->organization_id !== $org->id) {
            return response()->json(
                AbstractResource::error('Department not found'),
                404
            );
        }

        // Verify user belongs to organization
        if (Auth::user()->organization_id !== $org->id && !Auth::user()->isSuperAdmin()) {
            return response()->json(
                AbstractResource::error('Unauthorized'),
                403
            );
        }

        $members = $department->users()
            ->get()
            ->map(fn($user) => [
                'uuid' => $user->uuid,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->pivot->role,
            ]);

        return response()->json(
            AbstractResource::success([
                'department' => [
                    'uuid' => $department->uuid,
                    'name' => $department->name,
                    'slug' => $department->slug,
                    'description' => $department->description,
                    'status' => $department->status,
                    'members' => $members,
                    'members_count' => count($members),
                    'created_at' => $department->created_at->toISOString(),
                    'updated_at' => $department->updated_at->toISOString(),
                ]
            ])
        );
    }

    /**
     * Update department
     *
     * Update department details.
     * Requires org.manage permission.
     *
     * @authenticated
     *
     * @urlParam org integer required The organization ID. Example: 1
     * @urlParam department integer required The department ID. Example: 1
     * @bodyParam name string Department name. Example: Public Relations
     * @bodyParam description string Department description. Example: PR Department
     * @bodyParam status string Department status (active, inactive). Example: active
     *
     * @response 200 {
     *   "success": true,
     *   "message": "Department updated successfully",
     *   "data": {
     *     "department": {
     *       "id": 1,
     *       "name": "Public Relations",
     *       "slug": "public-relations",
     *       "status": "active",
     *       "updated_at": "2025-01-01T00:00:00.000000Z"
     *     }
     *   }
     * }
     */
    public function update(Request $request, Organization $org, Department $department): JsonResponse
    {
        // Verify department belongs to organization
        if ($department->organization_id !== $org->id) {
            return response()->json(
                AbstractResource::error('Department not found'),
                404
            );
        }

        // Verify user has permission to manage organization
        if (!Auth::user()->can('org.manage', $org) && !Auth::user()->isSuperAdmin()) {
            return response()->json(
                AbstractResource::error('Unauthorized'),
                403
            );
        }

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255', new NoHtml()],
            'description' => ['nullable', 'string', 'max:1000', new NoHtml()],
            'status' => ['sometimes', 'in:active,inactive'],
        ]);

        if (isset($validated['name'])) {
            $validated['slug'] = Str::slug($validated['name']);
        }

        $department->update($validated);

        return response()->json(
            AbstractResource::success([
                'department' => [
                    'uuid' => $department->uuid,
                    'name' => $department->name,
                    'slug' => $department->slug,
                    'status' => $department->status,
                    'updated_at' => $department->updated_at->toISOString(),
                ]
            ], 'Department updated successfully')
        );
    }

    /**
     * Delete department
     *
     * Delete a department.
     * Requires org.manage permission.
     *
     * @authenticated
     *
     * @urlParam org integer required The organization ID. Example: 1
     * @urlParam department integer required The department ID. Example: 1
     *
     * @response 200 {
     *   "success": true,
     *   "message": "Department deleted successfully",
     *   "data": null
     * }
     */
    public function destroy(Organization $org, Department $department): JsonResponse
    {
        // Verify department belongs to organization
        if ($department->organization_id !== $org->id) {
            return response()->json(
                AbstractResource::error('Department not found'),
                404
            );
        }

        // Verify user has permission to manage organization
        if (!Auth::user()->can('org.manage', $org) && !Auth::user()->isSuperAdmin()) {
            return response()->json(
                AbstractResource::error('Unauthorized'),
                403
            );
        }

        $department->delete();

        return response()->json(
            AbstractResource::success(null, 'Department deleted successfully')
        );
    }
}
