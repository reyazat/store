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

    /**
     * Delete an option value.
     * Checks if the value is used in product variants before deletion.
     *
     * @throws \RuntimeException if value is used in variants
     */
    public function deleteOptionValue(OptionValue $optionValue): void
    {
        // Check if value is used in any product variants
        $variantCount = DB::table('variant_option_values')
            ->where('option_value_id', $optionValue->id)
            ->count();

        if ($variantCount > 0) {
            throw new \RuntimeException(
                sprintf(
                    'این مقدار ویژگی در %d محصول استفاده شده است و قابل حذف نیست.',
                    $variantCount
                )
            );
        }

        $optionValue->delete();
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
