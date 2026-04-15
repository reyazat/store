<?php

namespace App\Actions\TaxClass;

use App\Models\TaxClass;
use App\Services\TaxClassService;

class DestroyTaxClassAction
{
    public function __construct(private readonly TaxClassService $taxClassService)
    {
    }

    public function execute(TaxClass $taxClass): void
    {
        $this->taxClassService->deleteTaxClass($taxClass);
    }
}
