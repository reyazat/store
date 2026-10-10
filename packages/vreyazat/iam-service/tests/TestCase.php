<?php

namespace Tests;

use IamApi\Models\Organization;
use IamApi\Models\PasswordHistory;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

class_alias(Organization::class, 'App\\Models\\Organization');
class_alias(PasswordHistory::class, 'App\\Models\\PasswordHistory');

abstract class TestCase extends BaseTestCase
{
    //
}
