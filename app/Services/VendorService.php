<?php

namespace App\Services;

use App\Models\Vendor;
use App\Repositories\Contracts\VendorRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Throwable;

class VendorService
{
    public function __construct(private readonly VendorRepositoryInterface $vendorRepository)
    {
    }

    public function getVendors(array $filters = []): LengthAwarePaginator
    {
        return $this->vendorRepository->getAll($filters);
    }

    public function getVendorById(int $id): ?Vendor
    {
        return $this->vendorRepository->findById($id);
    }

    /**
     * @throws Throwable
     */
    public function createVendor(array $data): Vendor
    {
        return DB::transaction(function () use ($data) {
            return Vendor::query()->create($this->vendorAttributes($data));
        });
    }

    /**
     * @throws Throwable
     */
    public function updateVendor(Vendor $vendor, array $data): Vendor
    {
        return DB::transaction(function () use ($vendor, $data) {
            $vendor->update($this->vendorAttributes($data));

            return $vendor->refresh();
        });
    }

    public function deleteVendor(Vendor $vendor): void
    {
        Vendor::query()->whereKey($vendor->getKey())->delete();
    }

    private function vendorAttributes(array $data): array
    {
        return Arr::only($data, [
            'name',
            'slug',
            'description',
            'is_platform',
            'commission_rate',
            'status',
            'approval_status',
        ]);
    }
}
