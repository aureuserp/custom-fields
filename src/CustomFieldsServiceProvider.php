<?php

namespace Webkul\CustomFields;

use Filament\Panel;
use Filament\Support\Assets\Css;
use Filament\Support\Facades\FilamentAsset;
use Illuminate\Support\Facades\Gate;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Webkul\CustomFields\Models\Field;

class CustomFieldsServiceProvider extends PackageServiceProvider
{
    public static string $name = 'custom-fields';

    public static string $viewNamespace = 'custom-fields';

    public function configurePackage(Package $package): void
    {
        $package
            ->name(static::$name)
            ->hasConfigFile(['custom-fields', 'filament-shield'])
            ->hasTranslations()
            ->hasMigration('2024_11_13_052541_create_custom_fields_table')
            ->runsMigrations();
    }

    public function packageRegistered(): void
    {
        $this->app->singleton(CustomFieldsPlugin::class);
    }

    public function packageBooted(): void
    {
        FilamentAsset::register([
            Css::make('custom-fields', __DIR__.'/../resources/dist/custom-fields.css'),
        ], 'aureuserp/custom-fields');
    }
}
