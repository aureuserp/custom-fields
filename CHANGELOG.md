# 🚀 CHANGELOG — v1.0.1

### ✨ Improvements

* Updated README requirements to reflect Laravel 11.28+, 12, and 13 support

### 🐛 Fixes

* #2 [fixed] - Installation failing on Laravel 13 due to the `spatie/eloquent-sortable ^4.0` constraint

# 🚀 CHANGELOG — v1.0.0

### 🧩 Features

* Initial release of the **Custom Fields** plugin for Filament v5.
* Runtime custom fields for any Eloquent model and Filament resource — no migrations required from the end-user.
* Admin CRUD (`FieldResource`) for managing custom field definitions.
* `HasCustomFields` Eloquent concern that auto-merges custom columns into the model's `fillable` and `casts`.
* `HasCustomFields` Filament resource concern with five merge helpers to inject fields into forms, tables, filters, and infolists.
* Filament components: `CustomFields` (forms), `CustomColumns` (tables), `CustomFilters` (filters), and `CustomEntries` (infolists).
* `CustomFieldsColumnManager` to create, update, and delete the underlying database columns at runtime.
* `CustomFieldsPlugin` for registering the plugin with a Filament panel.
* Field types via the `FieldType` enum (text, textarea, select, checkbox, radio, toggle, checkbox list, datetime, editor, markdown, color) and the `InputType` enum.
* Publishable configuration file (`config/custom-fields.php`).
* Publishable and customisable CSS assets registered via `CustomFieldsServiceProvider`.
* Model factory and migration for the custom fields table.
* Translations for the shipped strings.
