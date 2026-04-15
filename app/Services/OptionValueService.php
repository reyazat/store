<?php

namespace App\Services;

use App\Models\OptionValue;
use App\Repositories\Contracts\OptionValueRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Throwable;

class OptionValueService
{
    public function __construct(private readonly OptionValueRepositoryInterface $optionValueRepository)
    {
    }

    public function getOptionValues(array $filters = []): LengthAwarePaginator
    {
        return $this->optionValueRepository->getAll($filters);
    }

    public function getOptionValueById(int $id): ?OptionValue
    {
        return $this->optionValueRepository->findById($id);
    }

    /**
     * @throws Throwable
     */
    public function createOptionValue(array $data): OptionValue
    {
        return DB::transaction(function () use ($data) {
            return OptionValue::query()->create($this->optionValueAttributes($data));
        });
    }

    /**
     * @throws Throwable
     */
    public function updateOptionValue(OptionValue $optionValue, array $data): OptionValue
    {
        return DB::transaction(function () use ($optionValue, $data) {
            $optionValue->update($this->optionValueAttributes($data));

            return $optionValue->refresh();
        });
    }

    public function deleteOptionValue(OptionValue $optionValue): void
    {
        OptionValue::query()->whereKey($optionValue->getKey())->delete();
    }

    private function optionValueAttributes(array $data): array
    {
        return Arr::only($data, [
            'option_id',
            'value',
            'sorted',
        ]);
    }
}
