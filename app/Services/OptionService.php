<?php

namespace App\Services;

use App\Models\Option;
use App\Repositories\Contracts\OptionRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Throwable;

class OptionService
{
    public function __construct(private readonly OptionRepositoryInterface $optionRepository)
    {
    }

    public function getOptions(array $filters = []): LengthAwarePaginator
    {
        return $this->optionRepository->getAll($filters);
    }

    public function getOptionById(int $id): ?Option
    {
        return $this->optionRepository->findById($id);
    }

    /**
     * @throws Throwable
     */
    public function createOption(array $data): Option
    {
        return DB::transaction(function () use ($data) {
            return Option::query()->create($this->optionAttributes($data));
        });
    }

    /**
     * @throws Throwable
     */
    public function updateOption(Option $option, array $data): Option
    {
        return DB::transaction(function () use ($option, $data) {
            $option->update($this->optionAttributes($data));

            return $option->refresh();
        });
    }

    /**
     * Delete an option with cascade to values.
     * Checks if the option is used in product variants before deletion.
     *
     * @throws \RuntimeException if option is used in variants
     */
    public function deleteOption(Option $option): void
    {
        // Check if option is used in any product variants
        $variantCount = DB::table('variant_option_values')
            ->where('option_id', $option->id)
            ->count();

        if ($variantCount > 0) {
            throw new \RuntimeException(
                sprintf(
                    'این ویژگی در %d محصول استفاده شده است و قابل حذف نیست. ابتدا باید از محصولات حذف شود.',
                    $variantCount
                )
            );
        }

        // DB cascade will handle:
        // - option_values (onDelete cascade)
        // - option_product (onDelete cascade)
        // Soft delete the option
        $option->delete();
    }

    private function optionAttributes(array $data): array
    {
        return Arr::only($data, [
            'name',
            'slug',
            'type',
            'sorted',
        ]);
    }
}
