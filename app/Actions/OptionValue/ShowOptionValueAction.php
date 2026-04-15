<?php

namespace App\Actions\OptionValue;

use App\Models\OptionValue;

class ShowOptionValueAction
{
    public function execute(OptionValue $optionValue): OptionValue
    {
        return $optionValue->load(['option']);
    }
}
