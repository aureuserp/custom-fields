<?php

namespace Webkul\CustomFields\Concerns;

use BackedEnum;
use Closure;
use Filament\Pages\Enums\SubNavigationPosition;
use UnitEnum;

/**
 * Shield-style fluent configuration API for CustomFieldsPlugin.
 *
 * Every setter stores the raw value (scalar | Closure | enum); every getter
 * resolves Closures on read and falls back to getPluginDefaults() when the
 * plugin user never called the setter.
 *
 * Consumers:
 *
 *     CustomFieldsPlugin::make()
 *         ->navigationGroup('Settings')
 *         ->navigationIcon('heroicon-o-puzzle-piece')
 *         ->navigationSort(50)
 *         ->cluster(\App\Filament\Clusters\AdminTools::class)
 *         ->slug('admin/custom-fields')
 *         ->navigationBadge(fn () => Field::count());
 */
trait HasPluginConfiguration
{
    protected Closure | string | null $navigationLabel = null;

    protected Closure | string | UnitEnum | null $navigationGroup = null;

    protected BackedEnum | Closure | string | null $navigationIcon = null;

    protected BackedEnum | Closure | string | null $activeNavigationIcon = null;

    protected Closure | int | null $navigationSort = null;

    protected Closure | string | null $navigationBadge = null;

    protected array | Closure | string | null $navigationBadgeColor = null;

    protected Closure | string | null $navigationBadgeTooltip = null;

    protected Closure | string | null $navigationParentItem = null;

    protected Closure | SubNavigationPosition | null $subNavigationPosition = null;

    protected Closure | string | null $modelLabel = null;

    protected Closure | string | null $pluralModelLabel = null;

    protected Closure | string | null $slug = null;

    protected ?string $cluster = null;

    protected ?string $tenantRelationshipName = null;

    protected bool | Closure | null $shouldRegisterNavigation = null;

    protected bool | Closure | null $shouldRegisterResource = null;

    abstract protected function getPluginDefaults(): array;

    public function navigationLabel(Closure | string | null $label): static
    {
        $this->navigationLabel = $label;

        return $this;
    }

    public function navigationGroup(Closure | string | UnitEnum | null $group): static
    {
        $this->navigationGroup = $group;

        return $this;
    }

    public function navigationIcon(BackedEnum | Closure | string | null $icon): static
    {
        $this->navigationIcon = $icon;

        return $this;
    }

    public function activeNavigationIcon(BackedEnum | Closure | string | null $icon): static
    {
        $this->activeNavigationIcon = $icon;

        return $this;
    }

    public function navigationSort(Closure | int | null $sort): static
    {
        $this->navigationSort = $sort;

        return $this;
    }

    public function navigationBadge(Closure | string | null $badge): static
    {
        $this->navigationBadge = $badge;

        return $this;
    }

    public function navigationBadgeColor(array | Closure | string | null $color): static
    {
        $this->navigationBadgeColor = $color;

        return $this;
    }

    public function navigationBadgeTooltip(Closure | string | null $tooltip): static
    {
        $this->navigationBadgeTooltip = $tooltip;

        return $this;
    }

    public function navigationParentItem(Closure | string | null $item): static
    {
        $this->navigationParentItem = $item;

        return $this;
    }

    public function subNavigationPosition(Closure | SubNavigationPosition | null $position): static
    {
        $this->subNavigationPosition = $position;

        return $this;
    }

    public function modelLabel(Closure | string | null $label): static
    {
        $this->modelLabel = $label;

        return $this;
    }

    public function pluralModelLabel(Closure | string | null $label): static
    {
        $this->pluralModelLabel = $label;

        return $this;
    }

    public function slug(Closure | string | null $slug): static
    {
        $this->slug = $slug;

        return $this;
    }

    public function cluster(?string $cluster): static
    {
        $this->cluster = $cluster;

        return $this;
    }

    public function tenantRelationshipName(?string $name): static
    {
        $this->tenantRelationshipName = $name;

        return $this;
    }

    public function registerNavigation(bool | Closure $condition = true): static
    {
        $this->shouldRegisterNavigation = $condition;

        return $this;
    }

    public function registerResource(bool | Closure $condition = true): static
    {
        $this->shouldRegisterResource = $condition;

        return $this;
    }

    public function getNavigationLabel(): ?string
    {
        return $this->resolveOrDefault($this->navigationLabel, 'navigationLabel');
    }

    public function getNavigationGroup(): string | UnitEnum | null
    {
        $value = $this->navigationGroup !== null
            ? $this->resolveClosure($this->navigationGroup)
            : $this->pluginDefault('navigationGroup');

        return $value;
    }

    public function getNavigationIcon(): BackedEnum | string | null
    {
        $value = $this->navigationIcon !== null
            ? $this->resolveClosure($this->navigationIcon)
            : $this->pluginDefault('navigationIcon');

        return $value;
    }

    public function getActiveNavigationIcon(): BackedEnum | string | null
    {
        $value = $this->activeNavigationIcon !== null
            ? $this->resolveClosure($this->activeNavigationIcon)
            : $this->pluginDefault('activeNavigationIcon');

        return $value;
    }

    public function getNavigationSort(): ?int
    {
        $value = $this->navigationSort !== null
            ? $this->resolveClosure($this->navigationSort)
            : $this->pluginDefault('navigationSort');

        return $value === null ? null : (int) $value;
    }

    public function getNavigationBadge(): ?string
    {
        return $this->resolveOrDefault($this->navigationBadge, 'navigationBadge');
    }

    public function getNavigationBadgeColor(): array | string | null
    {
        $value = $this->navigationBadgeColor !== null
            ? $this->resolveClosure($this->navigationBadgeColor)
            : $this->pluginDefault('navigationBadgeColor');

        return $value;
    }

    public function getNavigationBadgeTooltip(): ?string
    {
        return $this->resolveOrDefault($this->navigationBadgeTooltip, 'navigationBadgeTooltip');
    }

    public function getNavigationParentItem(): ?string
    {
        return $this->resolveOrDefault($this->navigationParentItem, 'navigationParentItem');
    }

    public function getSubNavigationPosition(): ?SubNavigationPosition
    {
        $value = $this->subNavigationPosition !== null
            ? $this->resolveClosure($this->subNavigationPosition)
            : $this->pluginDefault('subNavigationPosition');

        return $value instanceof SubNavigationPosition ? $value : null;
    }

    public function getModelLabel(): ?string
    {
        return $this->resolveOrDefault($this->modelLabel, 'modelLabel');
    }

    public function getPluralModelLabel(): ?string
    {
        return $this->resolveOrDefault($this->pluralModelLabel, 'pluralModelLabel');
    }

    public function getSlug(): ?string
    {
        return $this->resolveOrDefault($this->slug, 'slug');
    }

    public function getCluster(): ?string
    {
        return $this->cluster ?? $this->pluginDefault('cluster');
    }

    public function getTenantRelationshipName(): ?string
    {
        return $this->tenantRelationshipName ?? $this->pluginDefault('tenantRelationshipName');
    }

    public function shouldRegisterNavigation(): bool
    {
        if ($this->shouldRegisterNavigation !== null) {
            return (bool) $this->resolveClosure($this->shouldRegisterNavigation);
        }

        return (bool) config('custom-fields.navigation.register', true);
    }

    public function shouldRegisterResource(): bool
    {
        if ($this->shouldRegisterResource !== null) {
            return (bool) $this->resolveClosure($this->shouldRegisterResource);
        }

        return (bool) config('custom-fields.resource.register', true);
    }

    protected function pluginDefault(string $key): mixed
    {
        $defaults = $this->getPluginDefaults();

        if (! array_key_exists($key, $defaults)) {
            return null;
        }

        return $this->resolveClosure($defaults[$key]);
    }

    protected function resolveClosure(mixed $value): mixed
    {
        return $value instanceof Closure ? $value() : $value;
    }

    protected function resolveOrDefault(mixed $value, string $defaultKey): ?string
    {
        $resolved = $value !== null
            ? $this->resolveClosure($value)
            : $this->pluginDefault($defaultKey);

        return $resolved === null ? null : (string) $resolved;
    }
}
