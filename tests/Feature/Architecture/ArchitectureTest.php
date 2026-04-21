<?php

use Filament\Contracts\Plugin;
use Illuminate\Database\Eloquent\Model;
use Spatie\EloquentSortable\Sortable;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Webkul\CustomFields\CustomFieldsColumnManager;
use Webkul\CustomFields\CustomFieldsPlugin;
use Webkul\CustomFields\CustomFieldsServiceProvider;
use Webkul\CustomFields\Enums\FieldType;
use Webkul\CustomFields\Enums\InputType;
use Webkul\CustomFields\Filament\Resources\FieldResource;
use Webkul\CustomFields\Models\Field;

it('Field model extends Eloquent Model and implements Sortable', function () {
    expect(is_subclass_of(Field::class, Model::class))->toBeTrue();
    expect(in_array(Sortable::class, class_implements(Field::class), true))->toBeTrue();
});

it('plugin implements Filament Plugin contract', function () {
    expect(in_array(Plugin::class, class_implements(CustomFieldsPlugin::class), true))->toBeTrue();
});

it('service provider extends Spatie PackageServiceProvider', function () {
    expect(is_subclass_of(CustomFieldsServiceProvider::class, PackageServiceProvider::class))->toBeTrue();
});

it('core classes exist', function () {
    expect(class_exists(Field::class))->toBeTrue();
    expect(class_exists(FieldResource::class))->toBeTrue();
    expect(class_exists(CustomFieldsColumnManager::class))->toBeTrue();
});

it('enums are defined', function () {
    expect(enum_exists(FieldType::class))->toBeTrue();
    expect(enum_exists(InputType::class))->toBeTrue();
});

arch('no debug calls leak into shipped code')
    ->expect('Webkul\\CustomFields')
    ->not->toUse(['dd', 'dump', 'var_dump', 'ray', 'die', 'exit']);
