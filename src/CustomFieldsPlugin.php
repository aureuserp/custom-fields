<?php

namespace Webkul\CustomFields;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Webkul\CustomFields\Concerns\HasPluginConfiguration;

class CustomFieldsPlugin implements Plugin
{
    use HasPluginConfiguration;

    public function getId(): string
    {
        return 'custom-fields';
    }

    public function register(Panel $panel): void
    {
        if (! $this->shouldRegisterResource()) {
            return;
        }

        $panel->when($panel->getId() === 'admin', function (Panel $panel) {
            $panel->discoverResources(
                in: __DIR__.'/Filament/Resources',
                for: 'Webkul\\CustomFields\\Filament\\Resources'
            );
        });
    }

    public function boot(Panel $panel): void
    {
        //
    }

    public static function make(): static
    {
        return app(static::class);
    }

    public static function get(): static
    {
        try {
            return filament(app(static::class)->getId());
        } catch (\Throwable) {
            return app(static::class);
        }
    }

    protected function getPluginDefaults(): array
    {
        return [
            'navigationLabel'        => config('custom-fields.navigation.label')
                ?? fn () => __('custom-fields::filament/resources/field.navigation.title'),
            'navigationGroup'        => config('custom-fields.navigation.group')
                ?? fn () => __('custom-fields::filament/resources/field.navigation.group'),
            'navigationIcon'         => config('custom-fields.navigation.icon') ?? 'heroicon-o-puzzle-piece',
            'activeNavigationIcon'   => config('custom-fields.navigation.active_icon'),
            'navigationSort'         => config('custom-fields.navigation.sort') ?? 5,
            'navigationBadge'        => config('custom-fields.navigation.badge'),
            'navigationBadgeColor'   => config('custom-fields.navigation.badge_color'),
            'navigationBadgeTooltip' => config('custom-fields.navigation.badge_tooltip'),
            'navigationParentItem'   => config('custom-fields.navigation.parent_item'),
            'subNavigationPosition'  => config('custom-fields.navigation.sub_position'),
            'modelLabel'             => config('custom-fields.resource.model_label')
                ?? fn () => __('custom-fields::filament/resources/field.navigation.title'),
            'pluralModelLabel'       => config('custom-fields.resource.plural_model_label')
                ?? fn () => __('custom-fields::filament/resources/field.navigation.title'),
            'slug'                   => config('custom-fields.resource.slug') ?? 'fields',
            'cluster'                => config('custom-fields.resource.cluster'),
            'tenantRelationshipName' => config('custom-fields.resource.tenant_relationship'),
        ];
    }
}
