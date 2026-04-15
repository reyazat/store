<?php

namespace App\Actions\Option;

use App\Models\Option;

class ShowOptionAction
{
    public function execute(Option $option): Option
    {
        return $option->load(['values']);
    }
}
