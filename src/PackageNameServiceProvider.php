<?php

declare(strict_types=1);

namespace VendorName\PackageName;

use Illuminate\Support\ServiceProvider;

class PackageNameServiceProvider extends ServiceProvider
{
    #[\Override]
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/skeleton-laravel.php', 'skeleton-laravel');
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../config/skeleton-laravel.php' => config_path('skeleton-laravel.php'),
        ], 'skeleton-laravel-config');
    }
}
