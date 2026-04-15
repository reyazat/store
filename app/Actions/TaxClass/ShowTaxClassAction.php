<?php

namespace App\Actions\TaxClass;

use App\Models\TaxClass;

class ShowTaxClassAction
{
    public function execute(TaxClass $taxClass): TaxClass
    {
        return $taxClass;
    }
}
