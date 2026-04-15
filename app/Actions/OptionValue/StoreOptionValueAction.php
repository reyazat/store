<?php

namespace App\Actions\OptionValue;

use App\Http\Requests\OptionValue\OptionValueStoreRequest;
use App\Models\OptionValue;
use App\Services\OptionValueService;
use Throwable;

class StoreOptionValueAction
{
    public function __construct(private readonly OptionValueService $optionValueService)
    {
    }

    /**
     * @throws Throwable
     */
    public function execute(OptionValueStoreRequest $request): OptionValue
    {
        return $this->optionValueService->createOptionValue($request->validatedData())->load(['option']);
    }
}
