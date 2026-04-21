<?php

namespace Webkul\CustomFields;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Webkul\CustomFields\Concerns\HasPluginConfiguration;
use Webkul\CustomFields\Filament\Resources\FieldResource;

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
        // Return a plain instance whenever no Filament panel is booted
        // (e.g. during `package:discover` / CLI / early autoload). The Resource
        // can still read defaults via getPluginDefaults().
        try {
            /** @var static $plugin */
            $plugin = filament(app(static::class)->getId());

            return $plugin;
        } catch (\Throwable) {
            return app(static::class);
        }
    }

    /**
     * Fallback values when the consumer hasn't called a fluent setter.
     *
     * Reads from config/custom-fields.php first (publishable via
     * `php artisan vendor:publish --tag=custom-fields-config`) and only
     * falls back to the hardcoded defaults below when the config key is
     * null / unset.
     *
     * Resolution order: fluent setter → this array → translated string.
     *
     * @return array<string, mixed>
     */
    protected function getPluginDefaults(): array
    {
        return [
            'navigationLabel'        => config('custom-fields.navigation.label')
                ?? fn () => __('custom-fields::filament/resources/field.navigation.title'),
            'navigationGroup'        => config('custom-fields.navigation.group')
                ?? fn () => __('custom-fields::filament/resources/field.navigation.group'),
            'navigationIcon'         => config('custom-fields.navigation.icon') ?? 'heroicon-o-adjustments-horizontal',
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
