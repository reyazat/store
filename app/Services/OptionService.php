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

    public function deleteOption(Option $option): void
    {
        Option::query()->whereKey($option->getKey())->delete();
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
