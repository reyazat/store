<?php

namespace App\Actions\Manufacturer;

use App\Models\Manufacturer;
use App\Services\ManufacturerService;

class DestroyManufacturerAction
{
    public function __construct(private readonly ManufacturerService $manufacturerService)
    {
    }

    public function execute(Manufacturer $manufacturer): void
    {
        $this->manufacturerService->deleteManufacturer($manufacturer);
    }
}
