<?php

namespace App\Services;

use App\Models\TaxClass;
use App\Repositories\Contracts\TaxClassRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Throwable;

class TaxClassService
{
    public function __construct(private readonly TaxClassRepositoryInterface $taxClassRepository)
    {
    }

    public function getTaxClasses(array $filters = []): LengthAwarePaginator
    {
        return $this->taxClassRepository->getAll($filters);
    }

    public function getTaxClassById(int $id): ?TaxClass
    {
        return $this->taxClassRepository->findById($id);
    }

    /**
     * @throws Throwable
     */
    public function createTaxClass(array $data): TaxClass
    {
        return DB::transaction(function () use ($data) {
            return TaxClass::query()->create($this->taxClassAttributes($data));
        });
    }

    /**
     * @throws Throwable
     */
    public function updateTaxClass(TaxClass $taxClass, array $data): TaxClass
    {
        return DB::transaction(function () use ($taxClass, $data) {
            $taxClass->update($this->taxClassAttributes($data));

            return $taxClass->refresh();
        });
    }

    public function deleteTaxClass(TaxClass $taxClass): void
    {
        TaxClass::query()->whereKey($taxClass->getKey())->delete();
    }

    private function taxClassAttributes(array $data): array
    {
        return Arr::only($data, [
            'name',
            'rate',
            'type',
            'status',
        ]);
    }
}
