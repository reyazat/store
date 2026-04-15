<?php

namespace App\Actions\Manufacturer;

use App\Http\Requests\Manufacturer\ManufacturerUpdateRequest;
use App\Models\Manufacturer;
use App\Services\ManufacturerService;
use Throwable;

class UpdateManufacturerAction
{
    public function __construct(private readonly ManufacturerService $manufacturerService)
    {
    }

    /**
     * @throws Throwable
     */
    public function execute(ManufacturerUpdateRequest $request, Manufacturer $manufacturer): Manufacturer
    {
        return $this->manufacturerService->updateManufacturer($manufacturer, $request->validatedData());
    }
}
