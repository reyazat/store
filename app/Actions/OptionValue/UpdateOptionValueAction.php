<?php

namespace App\Actions\OptionValue;

use App\Http\Requests\OptionValue\OptionValueUpdateRequest;
use App\Models\OptionValue;
use App\Services\OptionValueService;
use Throwable;

class UpdateOptionValueAction
{
    public function __construct(private readonly OptionValueService $optionValueService)
    {
    }

    /**
     * @throws Throwable
     */
    public function execute(OptionValueUpdateRequest $request, OptionValue $optionValue): OptionValue
    {
        return $this->optionValueService->updateOptionValue($optionValue, $request->validatedData())->load(['option']);
    }
}
