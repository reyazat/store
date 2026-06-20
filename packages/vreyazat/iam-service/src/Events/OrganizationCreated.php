<?php

namespace IamApi\Events;

use IamApi\Models\Organization;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrganizationCreated
{
    use Dispatchable, SerializesModels;

    public Organization $organization;

    public function __construct(Organization $organization)
    {
        $this->organization = $organization;
    }
}
