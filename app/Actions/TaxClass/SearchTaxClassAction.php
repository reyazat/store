<?php

namespace App\Actions\TaxClass;

use App\Http\Requests\TaxClass\TaxClassSearchRequest;
use App\Services\TaxClassService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class SearchTaxClassAction
{
    public function __construct(private readonly TaxClassService $taxClassService)
    {
    }

    public function execute(TaxClassSearchRequest $request): LengthAwarePaginator
    {
        return $this->taxClassService->getTaxClasses($request->validatedFilters());
    }
}
