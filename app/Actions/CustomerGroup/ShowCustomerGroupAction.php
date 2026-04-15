<?php

namespace App\Actions\CustomerGroup;

use App\Models\CustomerGroup;

class ShowCustomerGroupAction
{
    public function execute(CustomerGroup $customerGroup): CustomerGroup
    {
        return $customerGroup->load(['users:id']);
    }
}
