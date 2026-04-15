<?php

namespace App\Actions\Option;

use App\Http\Requests\Option\OptionUpdateRequest;
use App\Models\Option;
use App\Services\OptionService;
use Throwable;

class UpdateOptionAction
{
    public function __construct(private readonly OptionService $optionService)
    {
    }

    /**
     * @throws Throwable
     */
    public function execute(OptionUpdateRequest $request, Option $option): Option
    {
        return $this->optionService->updateOption($option, $request->validatedData())->load(['values']);
    }
}
