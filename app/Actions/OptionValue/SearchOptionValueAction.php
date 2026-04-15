<?php

namespace App\Actions\OptionValue;

use App\Http\Requests\OptionValue\OptionValueSearchRequest;
use App\Services\OptionValueService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class SearchOptionValueAction
{
    public function __construct(private readonly OptionValueService $optionValueService)
    {
    }

    public function execute(OptionValueSearchRequest $request): LengthAwarePaginator
    {
        return $this->optionValueService->getOptionValues($request->validatedFilters());
    }
}
