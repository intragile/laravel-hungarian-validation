<?php

declare(strict_types=1);

namespace IntrAgile\HungarianValidation\Tests;

use IntrAgile\HungarianValidation\Laravel\HungarianValidationServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [HungarianValidationServiceProvider::class];
    }
}
