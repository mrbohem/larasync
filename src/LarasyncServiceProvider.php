<?php

namespace MrBohem\Larasync;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use MrBohem\Larasync\Commands\LarasyncCommand;
use Livewire\Livewire;
use MrBohem\Larasync\Http\Livewire\SyncDashboard;

class LarasyncServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('larasync')
            ->hasConfigFile()
            ->hasViews()
            ->hasMigration('create_larasync_table')
            ->hasCommand(LarasyncCommand::class)
            ->hasAssets()
            ->hasRoute('web');
    }

    public function packageBooted(): void
    {
        // Register Livewire components manually
        Livewire::component('larasync::sync-dashboard', SyncDashboard::class);
    }
}
