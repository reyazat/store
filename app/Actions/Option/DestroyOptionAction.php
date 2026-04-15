<?php

namespace App\Actions\Option;

use App\Models\Option;
use App\Services\OptionService;

class DestroyOptionAction
{
    public function __construct(private readonly OptionService $optionService)
    {
    }

    public function execute(Option $option): void
    {
        $this->optionService->deleteOption($option);
    }
}
