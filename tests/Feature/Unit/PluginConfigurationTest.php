<?php

use Filament\Pages\Enums\SubNavigationPosition;
use Webkul\CustomFields\CustomFieldsPlugin;

function freshPlugin(): CustomFieldsPlugin
{
    return app()->make(CustomFieldsPlugin::class, []);
}

describe('Navigation setters', function () {
    it('navigationLabel — setter + fallback default', function () {
        $p = freshPlugin();

        expect($p->getNavigationLabel())->toBeString();
        expect($p->getNavigationLabel())->not->toBe('');

        $p->navigationLabel('My Fields');

        expect($p->getNavigationLabel())->toBe('My Fields');
    });

    it('navigationGroup — setter + default', function () {
        $p = freshPlugin();

        expect($p->getNavigationGroup())->not->toBeNull();

        $p->navigationGroup('Settings');

        expect($p->getNavigationGroup())->toBe('Settings');
    });

    it('navigationIcon — setter + default heroicon', function () {
        $p = freshPlugin();

        expect($p->getNavigationIcon())->toBe('heroicon-o-adjustments-horizontal');

        $p->navigationIcon('heroicon-o-puzzle-piece');

        expect($p->getNavigationIcon())->toBe('heroicon-o-puzzle-piece');
    });

    it('activeNavigationIcon — returns null by default, stores override', function () {
        $p = freshPlugin();

        expect($p->getActiveNavigationIcon())->toBeNull();

        $p->activeNavigationIcon('heroicon-s-adjustments-horizontal');

        expect($p->getActiveNavigationIcon())->toBe('heroicon-s-adjustments-horizontal');
    });

    it('navigationSort — default 5, casts string to int via closure', function () {
        $p = freshPlugin();

        expect($p->getNavigationSort())->toBe(5);

        $p->navigationSort(42);

        expect($p->getNavigationSort())->toBe(42);
    });

    it('navigationBadge — null by default, accepts closure', function () {
        $p = freshPlugin();

        expect($p->getNavigationBadge())->toBeNull();

        $p->navigationBadge(fn () => '9+');

        expect($p->getNavigationBadge())->toBe('9+');
    });

    it('navigationBadgeColor — stores scalar or array', function () {
        $p = freshPlugin();
        expect($p->getNavigationBadgeColor())->toBeNull();

        $p->navigationBadgeColor('primary');
        expect($p->getNavigationBadgeColor())->toBe('primary');

        $p2 = freshPlugin()->navigationBadgeColor(['hue' => 200]);
        expect($p2->getNavigationBadgeColor())->toBe(['hue' => 200]);
    });

    it('navigationBadgeTooltip + navigationParentItem', function () {
        $p = freshPlugin()->navigationBadgeTooltip('Unread items')->navigationParentItem('Settings');

        expect($p->getNavigationBadgeTooltip())->toBe('Unread items');
        expect($p->getNavigationParentItem())->toBe('Settings');
    });

    it('subNavigationPosition — default null, stores enum', function () {
        $p = freshPlugin();
        expect($p->getSubNavigationPosition())->toBeNull();

        $p->subNavigationPosition(SubNavigationPosition::Top);
        expect($p->getSubNavigationPosition())->toBe(SubNavigationPosition::Top);
    });
});

describe('Identity setters', function () {
    it('modelLabel / pluralModelLabel — defaults from translations, overridable', function () {
        $p = freshPlugin();

        expect($p->getModelLabel())->toBeString();
        expect($p->getPluralModelLabel())->toBeString();

        $p->modelLabel('Dynamic Field')->pluralModelLabel('Dynamic Fields');

        expect($p->getModelLabel())->toBe('Dynamic Field');
        expect($p->getPluralModelLabel())->toBe('Dynamic Fields');
    });

    it('slug — default "fields", overridable', function () {
        $p = freshPlugin();
        expect($p->getSlug())->toBe('fields');

        $p->slug('admin/custom-fields');
        expect($p->getSlug())->toBe('admin/custom-fields');
    });
});

describe('Placement setters', function () {
    it('cluster — null by default', function () {
        expect(freshPlugin()->getCluster())->toBeNull();
    });

    it('cluster — stored verbatim when set', function () {
        $p = freshPlugin()->cluster(\stdClass::class);
        expect($p->getCluster())->toBe(\stdClass::class);
    });

    it('tenantRelationshipName — default null, stored when set', function () {
        $p = freshPlugin();
        expect($p->getTenantRelationshipName())->toBeNull();

        $p->tenantRelationshipName('company');
        expect($p->getTenantRelationshipName())->toBe('company');
    });
});

describe('Behaviour toggles', function () {
    it('registerNavigation — default true', function () {
        expect(freshPlugin()->shouldRegisterNavigation())->toBeTrue();
    });

    it('registerNavigation(false) — hides nav item', function () {
        expect(freshPlugin()->registerNavigation(false)->shouldRegisterNavigation())->toBeFalse();
    });

    it('registerResource — default true', function () {
        expect(freshPlugin()->shouldRegisterResource())->toBeTrue();
    });

    it('registerResource(false) — opt out entirely', function () {
        expect(freshPlugin()->registerResource(false)->shouldRegisterResource())->toBeFalse();
    });
});

describe('Closures', function () {
    it('any setter accepts a closure that is evaluated on read', function () {
        $p = freshPlugin()
            ->navigationLabel(fn () => 'Lazy Label')
            ->navigationSort(fn () => 99);

        expect($p->getNavigationLabel())->toBe('Lazy Label');
        expect($p->getNavigationSort())->toBe(99);
    });
});

describe('Config-file resolution (setter > config > default)', function () {
    it('falls back to config value when no setter was called', function () {
        config()->set('custom-fields.navigation.icon', 'heroicon-o-puzzle-piece');
        config()->set('custom-fields.navigation.sort', 99);
        config()->set('custom-fields.resource.slug', 'admin/custom-fields');

        $p = freshPlugin();

        expect($p->getNavigationIcon())->toBe('heroicon-o-puzzle-piece');
        expect($p->getNavigationSort())->toBe(99);
        expect($p->getSlug())->toBe('admin/custom-fields');
    });

    it('fluent setter beats config value', function () {
        config()->set('custom-fields.navigation.icon', 'heroicon-o-puzzle-piece');

        $p = freshPlugin()->navigationIcon('heroicon-o-star');

        expect($p->getNavigationIcon())->toBe('heroicon-o-star');
    });

    it('config toggle for registerNavigation / registerResource is honoured', function () {
        config()->set('custom-fields.navigation.register', false);
        config()->set('custom-fields.resource.register', false);

        $p = freshPlugin();

        expect($p->shouldRegisterNavigation())->toBeFalse();
        expect($p->shouldRegisterResource())->toBeFalse();
    });

    it('falls back to hardcoded default when both setter and config are null', function () {
        config()->set('custom-fields.navigation.icon', null);
        config()->set('custom-fields.navigation.sort', null);

        $p = freshPlugin();

        expect($p->getNavigationIcon())->toBe('heroicon-o-adjustments-horizontal');
        expect($p->getNavigationSort())->toBe(5);
    });
});

describe('Chainability', function () {
    it('every setter returns $this', function () {
        $p = freshPlugin()
            ->navigationLabel('A')
            ->navigationGroup('B')
            ->navigationIcon('C')
            ->navigationSort(1)
            ->navigationBadge('D')
            ->navigationBadgeColor('primary')
            ->slug('e')
            ->modelLabel('F')
            ->pluralModelLabel('G')
            ->cluster(\stdClass::class)
            ->registerNavigation(true)
            ->registerResource(true);

        expect($p)->toBeInstanceOf(CustomFieldsPlugin::class);
    });
});
