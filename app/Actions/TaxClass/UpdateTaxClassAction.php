<?php

namespace App\Actions\TaxClass;

use App\Http\Requests\TaxClass\TaxClassUpdateRequest;
use App\Models\TaxClass;
use App\Services\TaxClassService;
use Throwable;

class UpdateTaxClassAction
{
    public function __construct(private readonly TaxClassService $taxClassService)
    {
    }

    /**
     * @throws Throwable
     */
    public function execute(TaxClassUpdateRequest $request, TaxClass $taxClass): TaxClass
    {
        return $this->taxClassService->updateTaxClass($taxClass, $request->validatedData());
    }
}
