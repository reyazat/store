<?php

namespace App\Actions\OptionValue;

use App\Models\OptionValue;
use App\Services\OptionValueService;

class DestroyOptionValueAction
{
    public function __construct(private readonly OptionValueService $optionValueService)
    {
    }

    public function execute(OptionValue $optionValue): void
    {
        $this->optionValueService->deleteOptionValue($optionValue);
    }
}
