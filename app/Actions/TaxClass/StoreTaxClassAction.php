<?php

namespace App\Actions\TaxClass;

use App\Http\Requests\TaxClass\TaxClassStoreRequest;
use App\Models\TaxClass;
use App\Services\TaxClassService;
use Throwable;

class StoreTaxClassAction
{
    public function __construct(private readonly TaxClassService $taxClassService)
    {
    }

    /**
     * @throws Throwable
     */
    public function execute(TaxClassStoreRequest $request): TaxClass
    {
        return $this->taxClassService->createTaxClass($request->validatedData());
    }
}
