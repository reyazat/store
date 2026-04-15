<?php

namespace App\Actions\Manufacturer;

use App\Http\Requests\Manufacturer\ManufacturerSearchRequest;
use App\Services\ManufacturerService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class SearchManufacturerAction
{
    public function __construct(private readonly ManufacturerService $manufacturerService)
    {
    }

    public function execute(ManufacturerSearchRequest $request): LengthAwarePaginator
    {
        return $this->manufacturerService->getManufacturers($request->validatedFilters());
    }
}
