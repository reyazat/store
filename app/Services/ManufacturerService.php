<?php

namespace App\Services;

use App\Models\Manufacturer;
use App\Repositories\Contracts\ManufacturerRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Throwable;

class ManufacturerService
{
    public function __construct(private readonly ManufacturerRepositoryInterface $manufacturerRepository)
    {
    }

    public function getManufacturers(array $filters = []): LengthAwarePaginator
    {
        return $this->manufacturerRepository->getAll($filters);
    }

    public function getManufacturerById(int $id): ?Manufacturer
    {
        return $this->manufacturerRepository->findById($id);
    }

    /**
     * @throws Throwable
     */
    public function createManufacturer(array $data): Manufacturer
    {
        return DB::transaction(function () use ($data) {
            return Manufacturer::query()->create($this->manufacturerAttributes($data));
        });
    }

    /**
     * @throws Throwable
     */
    public function updateManufacturer(Manufacturer $manufacturer, array $data): Manufacturer
    {
        return DB::transaction(function () use ($manufacturer, $data) {
            $manufacturer->update($this->manufacturerAttributes($data));

            return $manufacturer->refresh();
        });
    }

    public function deleteManufacturer(Manufacturer $manufacturer): void
    {
        Manufacturer::query()->whereKey($manufacturer->getKey())->delete();
    }

    private function manufacturerAttributes(array $data): array
    {
        return Arr::only($data, [
            'name',
            'slug',
            'image',
            'status',
        ]);
    }
}
