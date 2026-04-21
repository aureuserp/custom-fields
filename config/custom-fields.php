<?php

/*
|--------------------------------------------------------------------------
| Custom Fields Plugin Configuration
|--------------------------------------------------------------------------
|
| Every value below is also exposed on CustomFieldsPlugin as a fluent setter
| (e.g. ->navigationGroup(), ->cluster(), ->registerResource(false)). The
| resolution order at runtime is:
|
|   1. Fluent setter    — called on the plugin instance in AdminPanelProvider
|   2. This config file — published with `vendor:publish --tag=custom-fields-config`
|   3. Hardcoded fallback in getPluginDefaults()
|
| Leave any key as `null` to let the plugin fall back to step 2 / 3.
|
*/

return [

    /*
    |--------------------------------------------------------------------------
    | Navigation
    |--------------------------------------------------------------------------
    |
    | Controls where and how the Custom Fields resource appears in the sidebar.
    |
    */

    'navigation' => [

        /*
        |----------------------------------------------------------------------
        | Navigation Label
        |----------------------------------------------------------------------
        |
        | The label shown in the sidebar. When null, the plugin falls back to
        | the translated `custom-fields::…navigation.title` string.
        |
        */

        'label' => null,

        /*
        |----------------------------------------------------------------------
        | Navigation Group
        |----------------------------------------------------------------------
        |
        | The group the resource is nested under in the sidebar. Accepts a
        | string or a UnitEnum. When null, the plugin falls back to the
        | translated `custom-fields::…navigation.group` string.
        |
        */

        'group' => null,

        /*
        |----------------------------------------------------------------------
        | Navigation Icon
        |----------------------------------------------------------------------
        |
        | A Heroicon name or BackedEnum instance rendered next to the label.
        |
        */

        'icon' => 'heroicon-o-puzzle-piece',

        /*
        |----------------------------------------------------------------------
        | Active Navigation Icon
        |----------------------------------------------------------------------
        |
        | The icon displayed when the resource page is active. When null, the
        | plugin falls back to the `icon` value above.
        |
        */

        'active_icon' => null,

        /*
        |----------------------------------------------------------------------
        | Navigation Sort
        |----------------------------------------------------------------------
        |
        | Controls ordering within the sidebar group. Lower values sort first.
        |
        */

        'sort' => 5,

        /*
        |----------------------------------------------------------------------
        | Navigation Badge
        |----------------------------------------------------------------------
        |
        | Optional badge text displayed on the nav item. Accepts a string or
        | null to hide the badge entirely.
        |
        */

        'badge' => null,

        /*
        |----------------------------------------------------------------------
        | Navigation Badge Color
        |----------------------------------------------------------------------
        |
        | The badge color. Accepts a scalar color name (e.g. 'primary') or an
        | [hue => value] array for custom palettes.
        |
        */

        'badge_color' => null,

        /*
        |----------------------------------------------------------------------
        | Navigation Badge Tooltip
        |----------------------------------------------------------------------
        |
        | Tooltip text displayed when the user hovers the badge.
        |
        */

        'badge_tooltip' => null,

        /*
        |----------------------------------------------------------------------
        | Parent Navigation Item
        |----------------------------------------------------------------------
        |
        | Nests this resource underneath another navigation item, matched by
        | its label.
        |
        */

        'parent_item' => null,

        /*
        |----------------------------------------------------------------------
        | Sub-Navigation Position
        |----------------------------------------------------------------------
        |
        | One of Filament\Pages\Enums\SubNavigationPosition::Start, Top, or End
        | — or null to use the default position.
        |
        */

        'sub_position' => null,

        /*
        |----------------------------------------------------------------------
        | Register Navigation Item
        |----------------------------------------------------------------------
        |
        | When false, the item is hidden from the sidebar but the underlying
        | route remains reachable.
        |
        */

        'register' => true,

    ],

    /*
    |--------------------------------------------------------------------------
    | Resource Identity & Placement
    |--------------------------------------------------------------------------
    |
    | Controls how the Custom Fields resource itself is registered, routed,
    | and labelled within the Filament admin panel.
    |
    */

    'resource' => [

        /*
        |----------------------------------------------------------------------
        | Register Resource
        |----------------------------------------------------------------------
        |
        | When false, the admin CRUD is disabled entirely. The trait and
        | table-injection APIs continue to function regardless.
        |
        */

        'register' => true,

        /*
        |----------------------------------------------------------------------
        | Resource Slug
        |----------------------------------------------------------------------
        |
        | The URL segment used for the resource. For example, 'fields' produces
        | /admin/fields while 'admin/custom-fields' produces
        | /admin/admin/custom-fields.
        |
        */

        'slug' => 'fields',

        /*
        |----------------------------------------------------------------------
        | Resource Cluster
        |----------------------------------------------------------------------
        |
        | The fully-qualified class name of a Filament Cluster to nest this
        | resource under. When null, the resource sits at the top level.
        |
        */

        'cluster' => null,

        /*
        |----------------------------------------------------------------------
        | Model Labels
        |----------------------------------------------------------------------
        |
        | The singular and plural model labels shown on forms, tables, and
        | breadcrumbs. When null, the plugin falls back to the translated
        | title string.
        |
        */

        'model_label'        => null,
        'plural_model_label' => null,

        /*
        |----------------------------------------------------------------------
        | Tenant Relationship
        |----------------------------------------------------------------------
        |
        | The multi-tenant relationship name defined on the Field model.
        |
        */

        'tenant_relationship' => null,

    ],

];
