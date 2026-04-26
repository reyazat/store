<?php

namespace IamApi\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \IamApi\IamApi
 */
class IamApi extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'iam-service';
    }
}
