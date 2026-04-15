<?php

namespace App\Services;

use App\Models\CustomerGroup;
use App\Repositories\Contracts\CustomerGroupRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Throwable;

class CustomerGroupService
{
    public function __construct(private readonly CustomerGroupRepositoryInterface $customerGroupRepository)
    {
    }

    public function getCustomerGroups(array $filters = []): LengthAwarePaginator
    {
        return $this->customerGroupRepository->getAll($filters);
    }

    public function getCustomerGroupById(int $id): ?CustomerGroup
    {
        return $this->customerGroupRepository->findById($id);
    }

    /**
     * @throws Throwable
     */
    public function createCustomerGroup(array $data): CustomerGroup
    {
        return DB::transaction(function () use ($data) {
            $group = CustomerGroup::query()->create($this->customerGroupAttributes($data));
            $this->syncUsers($group, $data);

            return $group->refresh();
        });
    }

    /**
     * @throws Throwable
     */
    public function updateCustomerGroup(CustomerGroup $customerGroup, array $data): CustomerGroup
    {
        return DB::transaction(function () use ($customerGroup, $data) {
            $customerGroup->update($this->customerGroupAttributes($data));
            $this->syncUsers($customerGroup, $data);

            return $customerGroup->refresh();
        });
    }

    public function deleteCustomerGroup(CustomerGroup $customerGroup): void
    {
        CustomerGroup::query()->whereKey($customerGroup->getKey())->delete();
    }

    private function customerGroupAttributes(array $data): array
    {
        return Arr::only($data, [
            'name',
            'slug',
            'description',
            'approve_customer',
            'status',
        ]);
    }

    private function syncUsers(CustomerGroup $group, array $data): void
    {
        if (array_key_exists('user_ids', $data)) {
            $group->users()->sync($data['user_ids'] ?? []);
        }
    }
}
