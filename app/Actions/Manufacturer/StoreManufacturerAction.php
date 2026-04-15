<?php

namespace App\Actions\Manufacturer;

use App\Http\Requests\Manufacturer\ManufacturerStoreRequest;
use App\Models\Manufacturer;
use App\Services\ManufacturerService;
use Throwable;

class StoreManufacturerAction
{
    public function __construct(private readonly ManufacturerService $manufacturerService)
    {
    }

    /**
     * @throws Throwable
     */
    public function execute(ManufacturerStoreRequest $request): Manufacturer
    {
        return $this->manufacturerService->createManufacturer($request->validatedData());
    }
}
