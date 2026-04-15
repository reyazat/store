<?php

namespace App\Actions\Option;

use App\Http\Requests\Option\OptionStoreRequest;
use App\Models\Option;
use App\Services\OptionService;
use Throwable;

class StoreOptionAction
{
    public function __construct(private readonly OptionService $optionService)
    {
    }

    /**
     * @throws Throwable
     */
    public function execute(OptionStoreRequest $request): Option
    {
        return $this->optionService->createOption($request->validatedData())->load(['values']);
    }
}
