<?php

namespace App\Actions\User;

use IamApi\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ListUsersAction
{
    public function execute(array $filters = []): LengthAwarePaginator
    {
        $query = User::query()->with('organization');

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

        $perPage = min((int) ($filters['per_page'] ?? 15), 100);

        return $query->latest()->paginate($perPage);
    }
}
