<?php

namespace App\Actions\Option;

use App\Http\Requests\Option\OptionSearchRequest;
use App\Services\OptionService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class SearchOptionAction
{
    public function __construct(private readonly OptionService $optionService)
    {
    }

    public function execute(OptionSearchRequest $request): LengthAwarePaginator
    {
        return $this->optionService->getOptions($request->validatedFilters());
    }
}
