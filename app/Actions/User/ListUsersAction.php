<?php

namespace App\Actions\User;

use IamApi\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ListUsersAction
{
    public function execute(array $filters = []): LengthAwarePaginator
    {
        $query = User::query()->with('organization');

        // Super admins must never appear in lists used for store owner selection
        if (!empty($filters['exclude_super_admin'])) {
            $query->where('is_super_admin', false);
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['is_locked']) && $filters['is_locked'] !== '') {
            if ($filters['is_locked'] === '1' || $filters['is_locked'] === true) {
                $query->whereNotNull('locked_until')->where('locked_until', '>', now());
            } else {
                $query->where(function ($q) {
                    $q->whereNull('locked_until')->orWhere('locked_until', '<=', now());
                });
            }
        }

        $perPage = min((int) ($filters['per_page'] ?? 15), 100);

        return $query->latest()->paginate($perPage);
    }
}
