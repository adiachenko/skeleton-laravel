<?php

declare(strict_types=1);

namespace VendorName\PackageName\Tests;

use Orchestra\Testbench\TestCase as OrchestraTestCase;

class TestCase extends OrchestraTestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            \VendorName\PackageName\PackageNameServiceProvider::class,
        ];
    }
}
