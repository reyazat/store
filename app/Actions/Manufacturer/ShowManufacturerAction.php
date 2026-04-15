<?php

namespace App\Actions\Manufacturer;

use App\Models\Manufacturer;

class ShowManufacturerAction
{
    public function execute(Manufacturer $manufacturer): Manufacturer
    {
        return $manufacturer;
    }
}
