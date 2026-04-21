<?php

/*
|--------------------------------------------------------------------------
| Custom Fields plugin configuration
|--------------------------------------------------------------------------
|
| Every value below is also exposed on CustomFieldsPlugin as a fluent setter
| (e.g. ->navigationGroup(), ->cluster(), ->registerResource(false)). The
| resolution order at runtime is:
|
|   1. Fluent setter   — called on the plugin instance in AdminPanelProvider
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

        // Navigation label. null → uses the translated `custom-fields::…navigation.title` string.
        'label' => null,

        // Navigation group. null → uses the translated `custom-fields::…navigation.group` string.
        // Pass a string or a UnitEnum.
        'group' => null,

        // Heroicon or BackedEnum name shown beside the label.
        'icon' => 'heroicon-o-adjustments-horizontal',

        // Icon when the resource page is active. null → falls back to `icon`.
        'active_icon' => null,

        // Lower sorts first.
        'sort' => 5,

        // Optional badge (string or null) shown on the nav item.
        'badge' => null,

        // Badge color — scalar (e.g. 'primary') or [hue => value] array.
        'badge_color' => null,

        // Tooltip shown when hovering the badge.
        'badge_tooltip' => null,

        // Nest this resource under another nav item by label.
        'parent_item' => null,

        // Filament\Pages\Enums\SubNavigationPosition::Start|Top|End — or null.
        'sub_position' => null,

        // false hides the item from the sidebar but keeps the route reachable.
        'register' => true,

    ],

    /*
    |--------------------------------------------------------------------------
    | Resource identity + placement
    |--------------------------------------------------------------------------
    */

    'resource' => [

        // false disables the admin CRUD entirely (trait + table-injection APIs still work).
        'register' => true,

        // URL segment — e.g. 'fields' → /admin/fields, 'admin/custom-fields' → /admin/admin/custom-fields.
        'slug' => 'fields',

        // FQCN of a Filament Cluster to nest this resource under. null → top level.
        'cluster' => null,

        // Filament model labels shown on forms, tables, and breadcrumbs.
        'model_label'        => null,   // null → falls back to translated title
        'plural_model_label' => null,   // null → falls back to translated title

        // Multi-tenant relationship name on the Field model.
        'tenant_relationship' => null,

    ],

];
