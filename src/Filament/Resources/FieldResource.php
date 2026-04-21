<?php

namespace Webkul\CustomFields\Filament\Resources;

use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Support\Enums\Alignment;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Enums\IconPosition;
use Filament\Support\Enums\TextSize;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Schema;
use BackedEnum;
use Filament\Pages\Enums\SubNavigationPosition;
use Webkul\CustomFields\CustomFieldsColumnManager;
use Webkul\CustomFields\CustomFieldsPlugin;
use Webkul\CustomFields\Filament\Resources\FieldResource\Pages\CreateField;
use Webkul\CustomFields\Filament\Resources\FieldResource\Pages\EditField;
use Webkul\CustomFields\Filament\Resources\FieldResource\Pages\ListFields;
use Webkul\CustomFields\Models\Field;

class FieldResource extends Resource
{
    protected static ?string $model = Field::class;

    protected static function plugin(): CustomFieldsPlugin
    {
        return CustomFieldsPlugin::get();
    }

    public static function getModelLabel(): string
    {
        return (string) (static::plugin()->getModelLabel()
            ?? __('custom-fields::filament/resources/field.navigation.title'));
    }

    public static function getPluralModelLabel(): string
    {
        return (string) (static::plugin()->getPluralModelLabel()
            ?? __('custom-fields::filament/resources/field.navigation.title'));
    }

    public static function getNavigationLabel(): string
    {
        return (string) (static::plugin()->getNavigationLabel()
            ?? __('custom-fields::filament/resources/field.navigation.title'));
    }

    public static function getNavigationGroup(): ?string
    {
        $group = static::plugin()->getNavigationGroup();

        if ($group === null) {
            return __('custom-fields::filament/resources/field.navigation.group');
        }

        return is_string($group) ? $group : (string) $group;
    }

    public static function getNavigationIcon(): BackedEnum | string | null
    {
        return static::plugin()->getNavigationIcon();
    }

    public static function getActiveNavigationIcon(): BackedEnum | string | null
    {
        return static::plugin()->getActiveNavigationIcon() ?? static::getNavigationIcon();
    }

    public static function getNavigationSort(): ?int
    {
        return static::plugin()->getNavigationSort() ?? 5;
    }

    public static function getNavigationBadge(): ?string
    {
        return static::plugin()->getNavigationBadge();
    }

    public static function getNavigationBadgeColor(): array | string | null
    {
        return static::plugin()->getNavigationBadgeColor();
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return static::plugin()->getNavigationBadgeTooltip();
    }

    public static function getSlug(?\Filament\Panel $panel = null): string
    {
        return (string) (static::plugin()->getSlug() ?? 'fields');
    }

    public static function getCluster(): ?string
    {
        return static::plugin()->getCluster() ?? parent::getCluster();
    }

    public static function getSubNavigationPosition(): SubNavigationPosition
    {
        return static::plugin()->getSubNavigationPosition() ?? SubNavigationPosition::Start;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::plugin()->shouldRegisterNavigation();
    }

    public static function form(\Filament\Schemas\Schema $schema): \Filament\Schemas\Schema
    {
        return $schema
            ->components([
                Group::make()
                    ->schema([
                        Section::make()
                            ->schema([
                                TextInput::make('name')
                                    ->label(__('custom-fields::filament/resources/field.form.sections.general.fields.name'))
                                    ->required()
                                    ->maxLength(255),
                                TextInput::make('code')
                                    ->required()
                                    ->label(__('custom-fields::filament/resources/field.form.sections.general.fields.code'))
                                    ->maxLength(255)
                                    ->disabledOn('edit')
                                    ->helperText(__('custom-fields::filament/resources/field.form.sections.general.fields.code-helper-text'))
                                    ->unique(ignoreRecord: true)
                                    ->notIn(function (Get $get) {
                                        if ($get('id') || ! $get('customizable_type')) {
                                            return [];
                                        }

                                        $table = app($get('customizable_type'))->getTable();

                                        return Schema::getColumnListing($table);
                                    })
                                    ->rules([
                                        'regex:/^[a-zA-Z_][a-zA-Z0-9_]*$/',
                                    ]),
                            ])
                            ->columns(2),

                        Section::make(__('custom-fields::filament/resources/field.form.sections.options.title'))
                            ->visible(fn (Get $get): bool => in_array($get('type'), [
                                'select',
                                'checkbox_list',
                                'radio',
                            ]))
                            ->schema([
                                Repeater::make('options')
                                    ->hiddenLabel()
                                    ->simple(
                                        TextInput::make('name')
                                            ->required()
                                            ->maxLength(255),
                                    )
                                    ->addActionLabel(__('custom-fields::filament/resources/field.form.sections.options.fields.add-option')),
                            ]),

                        Section::make(__('custom-fields::filament/resources/field.form.sections.form-settings.title'))
                            ->schema([
                                Group::make()
                                    ->schema(static::getFormSettingsSchema())
                                    ->statePath('form_settings'),
                            ]),

                        Section::make(__('custom-fields::filament/resources/field.form.sections.table-settings.title'))
                            ->schema(static::getTableSettingsSchema()),

                        Section::make(__('custom-fields::filament/resources/field.form.sections.infolist-settings.title'))
                            ->schema(static::getInfolistSettingsSchema()),
                    ])
                    ->columnSpan(['lg' => 2]),

                Group::make()
                    ->schema([
                        Section::make(__('custom-fields::filament/resources/field.form.sections.settings.title'))
                            ->schema([
                                Select::make('type')
                                    ->label(__('custom-fields::filament/resources/field.form.sections.settings.fields.type'))
                                    ->required()
                                    ->disabledOn('edit')
                                    ->searchable()
                                    ->native(false)
                                    ->live()
                                    ->options([
                                        'text'          => __('custom-fields::filament/resources/field.form.sections.settings.fields.type-options.text'),
                                        'textarea'      => __('custom-fields::filament/resources/field.form.sections.settings.fields.type-options.textarea'),
                                        'select'        => __('custom-fields::filament/resources/field.form.sections.settings.fields.type-options.select'),
                                        'checkbox'      => __('custom-fields::filament/resources/field.form.sections.settings.fields.type-options.checkbox'),
                                        'radio'         => __('custom-fields::filament/resources/field.form.sections.settings.fields.type-options.radio'),
                                        'toggle'        => __('custom-fields::filament/resources/field.form.sections.settings.fields.type-options.toggle'),
                                        'checkbox_list' => __('custom-fields::filament/resources/field.form.sections.settings.fields.type-options.checkbox-list'),
                                        'datetime'      => __('custom-fields::filament/resources/field.form.sections.settings.fields.type-options.datetime'),
                                        'editor'        => __('custom-fields::filament/resources/field.form.sections.settings.fields.type-options.editor'),
                                        'markdown'      => __('custom-fields::filament/resources/field.form.sections.settings.fields.type-options.markdown'),
                                        'color'         => __('custom-fields::filament/resources/field.form.sections.settings.fields.type-options.color'),
                                    ]),
                                Select::make('input_type')
                                    ->label(__('custom-fields::filament/resources/field.form.sections.settings.fields.input-type'))
                                    ->required()
                                    ->disabledOn('edit')
                                    ->native(false)
                                    ->visible(fn (Get $get): bool => $get('type') == 'text')
                                    ->options([
                                        'text'     => __('custom-fields::filament/resources/field.form.sections.settings.fields.input-type-options.text'),
                                        'email'    => __('custom-fields::filament/resources/field.form.sections.settings.fields.input-type-options.email'),
                                        'numeric'  => __('custom-fields::filament/resources/field.form.sections.settings.fields.input-type-options.numeric'),
                                        'integer'  => __('custom-fields::filament/resources/field.form.sections.settings.fields.input-type-options.integer'),
                                        'password' => __('custom-fields::filament/resources/field.form.sections.settings.fields.input-type-options.password'),
                                        'tel'      => __('custom-fields::filament/resources/field.form.sections.settings.fields.input-type-options.tel'),
                                        'url'      => __('custom-fields::filament/resources/field.form.sections.settings.fields.input-type-options.url'),
                                        'color'    => __('custom-fields::filament/resources/field.form.sections.settings.fields.input-type-options.color'),
                                    ]),
                                Toggle::make('is_multiselect')
                                    ->label(__('custom-fields::filament/resources/field.form.sections.settings.fields.is-multiselect'))
                                    ->required()
                                    ->visible(fn (Get $get): bool => $get('type') == 'select')
                                    ->live(),
                                TextInput::make('sort')
                                    ->label(__('custom-fields::filament/resources/field.form.sections.settings.fields.sort-order'))
                                    ->required()
                                    ->integer()
                                    ->maxLength(255),
                            ]),

                        Section::make(__('custom-fields::filament/resources/field.form.sections.resource.title'))
                            ->schema([
                                Select::make('customizable_type')
                                    ->label(__('custom-fields::filament/resources/field.form.sections.resource.fields.resource'))
                                    ->required()
                                    ->searchable()
                                    ->native(false)
                                    ->disabledOn('edit')
                                    ->options(fn () => collect(Filament::getResources())->filter(fn ($resource) => in_array('Webkul\CustomFields\Filament\Concerns\HasCustomFields', class_uses($resource)))->mapWithKeys(fn ($resource) => [
                                        $resource::getModel() => str($resource)->afterLast('\\')->toString(),
                                    ])),
                            ]),
                    ])
                    ->columnSpan(['lg' => 1]),
            ])
            ->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')
                    ->label(__('custom-fields::filament/resources/field.table.columns.code'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('name')
                    ->label(__('custom-fields::filament/resources/field.table.columns.name'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('type')
                    ->label(__('custom-fields::filament/resources/field.table.columns.type'))
                    ->sortable(),
                TextColumn::make('customizable_type')
                    ->label(__('custom-fields::filament/resources/field.table.columns.resource'))
                    ->description(fn (Field $record): string => str($record->customizable_type)->afterLast('\\')->toString().'Resource')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label(__('custom-fields::filament/resources/field.table.columns.created-at'))
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->label(__('custom-fields::filament/resources/field.table.filters.type'))
                    ->options([
                        'text'          => __('custom-fields::filament/resources/field.table.filters.type-options.text'),
                        'textarea'      => __('custom-fields::filament/resources/field.table.filters.type-options.textarea'),
                        'select'        => __('custom-fields::filament/resources/field.table.filters.type-options.select'),
                        'checkbox'      => __('custom-fields::filament/resources/field.table.filters.type-options.checkbox'),
                        'radio'         => __('custom-fields::filament/resources/field.table.filters.type-options.radio'),
                        'toggle'        => __('custom-fields::filament/resources/field.table.filters.type-options.toggle'),
                        'checkbox_list' => __('custom-fields::filament/resources/field.table.filters.type-options.checkbox-list'),
                        'datetime'      => __('custom-fields::filament/resources/field.table.filters.type-options.datetime'),
                        'editor'        => __('custom-fields::filament/resources/field.table.filters.type-options.editor'),
                        'markdown'      => __('custom-fields::filament/resources/field.table.filters.type-options.markdown'),
                        'color'         => __('custom-fields::filament/resources/field.table.filters.type-options.color'),
                    ]),
                SelectFilter::make('customizable_type')
                    ->label(__('custom-fields::filament/resources/field.table.filters.resource'))
                    ->options(fn () => collect(Filament::getResources())->filter(fn ($resource) => in_array('Webkul\CustomFields\Filament\Concerns\HasCustomFields', class_uses($resource)))->mapWithKeys(fn ($resource) => [
                        $resource::getModel() => str($resource)->afterLast('\\')->toString(),
                    ])),
            ])
            ->recordActions([
                ActionGroup::make([
                    EditAction::make()
                        ->hidden(fn ($record) => $record->trashed()),
                    RestoreAction::make()
                        ->successNotification(
                            Notification::make()
                                ->success()
                                ->title(__('custom-fields::filament/resources/field.table.actions.restore.notification.title'))
                                ->body(__('custom-fields::filament/resources/field.table.actions.restore.notification.body')),
                        ),
                    DeleteAction::make()
                        ->successNotification(
                            Notification::make()
                                ->success()
                                ->title(__('custom-fields::filament/resources/field.table.actions.delete.notification.title'))
                                ->body(__('custom-fields::filament/resources/field.table.actions.delete.notification.body')),
                        ),
                    ForceDeleteAction::make()
                        ->before(function ($record) {
                            CustomFieldsColumnManager::deleteColumn($record);
                        })
                        ->successNotification(
                            Notification::make()
                                ->success()
                                ->title(__('custom-fields::filament/resources/field.table.actions.force-delete.notification.title'))
                                ->body(__('custom-fields::filament/resources/field.table.actions.force-delete.notification.body')),
                        ),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    RestoreBulkAction::make()
                        ->successNotification(
                            Notification::make()
                                ->success()
                                ->title(__('custom-fields::filament/resources/field.table.bulk-actions.restore.notification.title'))
                                ->body(__('custom-fields::filament/resources/field.table.bulk-actions.restore.notification.body')),
                        ),
                    DeleteBulkAction::make()
                        ->successNotification(
                            Notification::make()
                                ->success()
                                ->title(__('custom-fields::filament/resources/field.table.bulk-actions.delete.notification.title'))
                                ->body(__('custom-fields::filament/resources/field.table.bulk-actions.delete.notification.body')),
                        ),
                    ForceDeleteBulkAction::make()
                        ->before(function ($records) {
                            foreach ($records as $record) {
                                CustomFieldsColumnManager::deleteColumn($record);
                            }
                        })
                        ->successNotification(
                            Notification::make()
                                ->success()
                                ->title(__('custom-fields::filament/resources/field.table.bulk-actions.force-delete.notification.title'))
                                ->body(__('custom-fields::filament/resources/field.table.bulk-actions.force-delete.notification.body')),
                        ),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListFields::route('/'),
            'create' => CreateField::route('/create'),
            'edit'   => EditField::route('/{record}/edit'),
        ];
    }

    public static function getFormSettingsSchema(): array
    {
        return [
            Fieldset::make(__('custom-fields::filament/resources/field.form.sections.form-settings.field-sets.validations.title'))
                ->schema([
                    Repeater::make('validations')
                        ->hiddenLabel()
                        ->schema([
                            Select::make('validation')
                                ->label(__('custom-fields::filament/resources/field.form.sections.form-settings.field-sets.validations.fields.validation'))
                                ->searchable()
                                ->required()
                                ->distinct()
                                ->live()
                                ->options(fn (Get $get): array => static::getTypeFormValidations($get('../../../type'))),
                            TextInput::make('field')
                                ->label(__('custom-fields::filament/resources/field.form.sections.form-settings.field-sets.validations.fields.field'))
                                ->required()
                                ->visible(fn (Get $get): bool => in_array($get('validation'), [
                                    'prohibitedIf',
                                    'prohibitedUnless',
                                    'requiredIf',
                                    'requiredUnless',
                                ])),
                            TextInput::make('value')
                                ->label(__('custom-fields::filament/resources/field.form.sections.form-settings.field-sets.validations.fields.value'))
                                ->required()
                                ->visible(fn (Get $get): bool => in_array($get('validation'), [
                                    'after',
                                    'afterOrEqual',
                                    'before',
                                    'beforeOrEqual',
                                    'different',
                                    'doesntEndWith',
                                    'doesntStartWith',
                                    'endsWith',
                                    'gt',
                                    'gte',
                                    'in',
                                    'lt',
                                    'lte',
                                    'maxSize',
                                    'minSize',
                                    'multipleOf',
                                    'notIn',
                                    'notRegex',
                                    'prohibitedIf',
                                    'prohibitedUnless',
                                    'prohibits',
                                    'regex',
                                    'requiredIf',
                                    'requiredUnless',
                                    'requiredWith',
                                    'requiredWithAll',
                                    'requiredWithout',
                                    'requiredWithoutAll',
                                    'rules',
                                    'same',
                                    'startsWith',
                                ])),
                        ])
                        ->addActionLabel(__('custom-fields::filament/resources/field.form.sections.form-settings.field-sets.validations.fields.add-validation'))
                        ->columns(3)
                        ->collapsible()
                        ->itemLabel(function (array $state, Get $get): ?string {
                            $validations = static::getTypeFormValidations($get('../type'));

                            return $validations[$state['validation']] ?? null;
                        }),
                ])
                ->columns(1),

            Fieldset::make(__('custom-fields::filament/resources/field.form.sections.form-settings.field-sets.additional-settings.title'))
                ->schema([
                    Repeater::make('settings')
                        ->hiddenLabel()
                        ->schema([
                            Select::make('setting')
                                ->label(__('custom-fields::filament/resources/field.form.sections.form-settings.field-sets.additional-settings.fields.setting'))
                                ->required()
                                ->distinct()
                                ->searchable()
                                ->live()
                                ->options(fn (Get $get): array => static::getTypeFormSettings($get('../../../type'))),
                            TextInput::make('value')
                                ->label(__('custom-fields::filament/resources/field.form.sections.form-settings.field-sets.additional-settings.fields.value'))
                                ->required()
                                ->maxLength(255)
                                ->visible(fn (Get $get): bool => in_array($get('setting'), [
                                    'autocapitalize',
                                    'autocomplete',
                                    'default',
                                    'disabledDates',
                                    'displayFormat',
                                    'format',
                                    'helperText',
                                    'hint',
                                    'hintIcon',
                                    'id',
                                    'loadingMessage',
                                    'locale',
                                    'mask',
                                    'noSearchResultsMessage',
                                    'offIcon',
                                    'onIcon',
                                    'placeholder',
                                    'prefix',
                                    'prefixIcon',
                                    'searchingMessage',
                                    'searchPrompt',
                                    'suffix',
                                    'suffixIcon',
                                    'timezone',
                                ])),
                            TextInput::make('value')
                                ->label(__('custom-fields::filament/resources/field.form.sections.form-settings.field-sets.additional-settings.fields.value'))
                                ->required()
                                ->numeric()
                                ->minValue(0)
                                ->maxValue(99999999999)
                                ->visible(fn (Get $get): bool => in_array($get('setting'), [
                                    'cols',
                                    'columns',
                                    'firstDayOfWeek',
                                    'hoursStep',
                                    'maxItems',
                                    'minItems',
                                    'minutesStep',
                                    'optionsLimit',
                                    'rows',
                                    'searchDebounce',
                                    'seconds',
                                    'secondsStep',
                                    'step',
                                ])),
                            Select::make('value')
                                ->label(__('custom-fields::filament/resources/field.form.sections.form-settings.field-sets.additional-settings.fields.color'))
                                ->required()
                                ->visible(fn (Get $get): bool => in_array($get('setting'), [
                                    'hintColor',
                                    'prefixIconColor',
                                    'suffixIconColor',
                                    'onColor',
                                    'offColor',
                                ]))
                                ->options([
                                    'danger'    => __('custom-fields::filament/resources/field.form.sections.form-settings.field-sets.additional-settings.fields.color-options.danger'),
                                    'info'      => __('custom-fields::filament/resources/field.form.sections.form-settings.field-sets.additional-settings.fields.color-options.info'),
                                    'primary'   => __('custom-fields::filament/resources/field.form.sections.form-settings.field-sets.additional-settings.fields.color-options.primary'),
                                    'secondary' => __('custom-fields::filament/resources/field.form.sections.form-settings.field-sets.additional-settings.fields.color-options.secondary'),
                                    'warning'   => __('custom-fields::filament/resources/field.form.sections.form-settings.field-sets.additional-settings.fields.color-options.warning'),
                                    'success'   => __('custom-fields::filament/resources/field.form.sections.form-settings.field-sets.additional-settings.fields.color-options.success'),
                                ]),
                            Select::make('value')
                                ->label(__('custom-fields::filament/resources/field.form.sections.form-settings.field-sets.additional-settings.fields.value'))
                                ->required()
                                ->visible(fn (Get $get): bool => in_array($get('setting'), [
                                    'gridDirection',
                                ]))
                                ->options([
                                    'row'    => __('custom-fields::filament/resources/field.form.sections.form-settings.field-sets.additional-settings.fields.grid-options.row'),
                                    'column' => __('custom-fields::filament/resources/field.form.sections.form-settings.field-sets.additional-settings.fields.grid-options.column'),
                                ]),
                            Select::make('value')
                                ->label(__('custom-fields::filament/resources/field.form.sections.form-settings.field-sets.additional-settings.fields.value'))
                                ->required()
                                ->visible(fn (Get $get): bool => in_array($get('setting'), [
                                    'inputMode',
                                ]))
                                ->options([
                                    'none'    => __('custom-fields::filament/resources/field.form.sections.form-settings.field-sets.additional-settings.fields.input-modes.none'),
                                    'text'    => __('custom-fields::filament/resources/field.form.sections.form-settings.field-sets.additional-settings.fields.input-modes.text'),
                                    'numeric' => __('custom-fields::filament/resources/field.form.sections.form-settings.field-sets.additional-settings.fields.input-modes.numeric'),
                                    'decimal' => __('custom-fields::filament/resources/field.form.sections.form-settings.field-sets.additional-settings.fields.input-modes.decimal'),
                                    'tel'     => __('custom-fields::filament/resources/field.form.sections.form-settings.field-sets.additional-settings.fields.input-modes.tel'),
                                    'search'  => __('custom-fields::filament/resources/field.form.sections.form-settings.field-sets.additional-settings.fields.input-modes.search'),
                                    'email'   => __('custom-fields::filament/resources/field.form.sections.form-settings.field-sets.additional-settings.fields.input-modes.email'),
                                    'url'     => __('custom-fields::filament/resources/field.form.sections.form-settings.field-sets.additional-settings.fields.input-modes.url'),
                                ]),
                        ])
                        ->addActionLabel(__('custom-fields::filament/resources/field.form.sections.form-settings.field-sets.additional-settings.fields.add-setting'))
                        ->columns(2)
                        ->collapsible()
                        ->itemLabel(function (array $state, Get $get): ?string {
                            $settings = static::getTypeFormSettings($get('../type'));

                            return $settings[$state['setting']] ?? null;
                        }),
                ])
                ->columns(1),
        ];
    }

    public static function getTypeFormValidations(?string $type): array
    {
        if (is_null($type)) {
            return [];
        }

        $commonValidations = [
            'gt'                 => __('custom-fields::filament/resources/field.form.sections.form-settings.validations.common.gt'),
            'gte'                => __('custom-fields::filament/resources/field.form.sections.form-settings.validations.common.gte'),
            'lt'                 => __('custom-fields::filament/resources/field.form.sections.form-settings.validations.common.lt'),
            'lte'                => __('custom-fields::filament/resources/field.form.sections.form-settings.validations.common.lte'),
            'maxSize'            => __('custom-fields::filament/resources/field.form.sections.form-settings.validations.common.max-size'),
            'minSize'            => __('custom-fields::filament/resources/field.form.sections.form-settings.validations.common.min-size'),
            'multipleOf'         => __('custom-fields::filament/resources/field.form.sections.form-settings.validations.common.multiple-of'),
            'nullable'           => __('custom-fields::filament/resources/field.form.sections.form-settings.validations.common.nullable'),
            'prohibited'         => __('custom-fields::filament/resources/field.form.sections.form-settings.validations.common.prohibited'),
            'prohibitedIf'       => __('custom-fields::filament/resources/field.form.sections.form-settings.validations.common.prohibited-if'),
            'prohibitedUnless'   => __('custom-fields::filament/resources/field.form.sections.form-settings.validations.common.prohibited-unless'),
            'prohibits'          => __('custom-fields::filament/resources/field.form.sections.form-settings.validations.common.prohibits'),
            'required'           => __('custom-fields::filament/resources/field.form.sections.form-settings.validations.common.required'),
            'requiredIf'         => __('custom-fields::filament/resources/field.form.sections.form-settings.validations.common.required-if'),
            'requiredIfAccepted' => __('custom-fields::filament/resources/field.form.sections.form-settings.validations.common.required-if-accepted'),
            'requiredUnless'     => __('custom-fields::filament/resources/field.form.sections.form-settings.validations.common.required-unless'),
            'requiredWith'       => __('custom-fields::filament/resources/field.form.sections.form-settings.validations.common.required-with'),
            'requiredWithAll'    => __('custom-fields::filament/resources/field.form.sections.form-settings.validations.common.required-with-all'),
            'requiredWithout'    => __('custom-fields::filament/resources/field.form.sections.form-settings.validations.common.required-without'),
            'requiredWithoutAll' => __('custom-fields::filament/resources/field.form.sections.form-settings.validations.common.required-without-all'),
            'rules'              => __('custom-fields::filament/resources/field.form.sections.form-settings.validations.common.rules'),
            'unique'             => __('custom-fields::filament/resources/field.form.sections.form-settings.validations.common.unique'),
        ];

        $typeValidations = match ($type) {
            'text' => [
                'alphaDash'       => __('custom-fields::filament/resources/field.form.sections.form-settings.validations.text.alpha-dash'),
                'alphaNum'        => __('custom-fields::filament/resources/field.form.sections.form-settings.validations.text.alpha-num'),
                'ascii'           => __('custom-fields::filament/resources/field.form.sections.form-settings.validations.text.ascii'),
                'doesntEndWith'   => __('custom-fields::filament/resources/field.form.sections.form-settings.validations.text.doesnt-end-with'),
                'doesntStartWith' => __('custom-fields::filament/resources/field.form.sections.form-settings.validations.text.doesnt-start-with'),
                'endsWith'        => __('custom-fields::filament/resources/field.form.sections.form-settings.validations.text.ends-with'),
                'filled'          => __('custom-fields::filament/resources/field.form.sections.form-settings.validations.text.filled'),
                'ip'              => __('custom-fields::filament/resources/field.form.sections.form-settings.validations.text.ip'),
                'ipv4'            => __('custom-fields::filament/resources/field.form.sections.form-settings.validations.text.ipv4'),
                'ipv6'            => __('custom-fields::filament/resources/field.form.sections.form-settings.validations.text.ipv6'),
                'length'          => __('custom-fields::filament/resources/field.form.sections.form-settings.validations.text.length'),
                'macAddress'      => __('custom-fields::filament/resources/field.form.sections.form-settings.validations.text.mac-address'),
                'maxLength'       => __('custom-fields::filament/resources/field.form.sections.form-settings.validations.text.max-length'),
                'minLength'       => __('custom-fields::filament/resources/field.form.sections.form-settings.validations.text.min-length'),
                'regex'           => __('custom-fields::filament/resources/field.form.sections.form-settings.validations.text.regex'),
                'startsWith'      => __('custom-fields::filament/resources/field.form.sections.form-settings.validations.text.starts-with'),
                'ulid'            => __('custom-fields::filament/resources/field.form.sections.form-settings.validations.text.ulid'),
                'uuid'            => __('custom-fields::filament/resources/field.form.sections.form-settings.validations.text.uuid'),
            ],

            'textarea' => [
                'filled'    => __('custom-fields::filament/resources/field.form.sections.form-settings.validations.textarea.filled'),
                'maxLength' => __('custom-fields::filament/resources/field.form.sections.form-settings.validations.textarea.max-length'),
                'minLength' => __('custom-fields::filament/resources/field.form.sections.form-settings.validations.textarea.min-length'),
            ],

            'select' => [
                'different' => __('custom-fields::filament/resources/field.form.sections.form-settings.validations.select.different'),
                'exists'    => __('custom-fields::filament/resources/field.form.sections.form-settings.validations.select.exists'),
                'in'        => __('custom-fields::filament/resources/field.form.sections.form-settings.validations.select.in'),
                'notIn'     => __('custom-fields::filament/resources/field.form.sections.form-settings.validations.select.not-in'),
                'same'      => __('custom-fields::filament/resources/field.form.sections.form-settings.validations.select.same'),
            ],

            'radio' => [],

            'checkbox' => [
                'accepted' => __('custom-fields::filament/resources/field.form.sections.form-settings.validations.checkbox.accepted'),
                'declined' => __('custom-fields::filament/resources/field.form.sections.form-settings.validations.checkbox.declined'),
            ],

            'toggle' => [
                'accepted' => __('custom-fields::filament/resources/field.form.sections.form-settings.validations.toggle.accepted'),
                'declined' => __('custom-fields::filament/resources/field.form.sections.form-settings.validations.toggle.declined'),
            ],

            'checkbox_list' => [
                'in'       => __('custom-fields::filament/resources/field.form.sections.form-settings.validations.checkbox-list.in'),
                'maxItems' => __('custom-fields::filament/resources/field.form.sections.form-settings.validations.checkbox-list.max-items'),
                'minItems' => __('custom-fields::filament/resources/field.form.sections.form-settings.validations.checkbox-list.min-items'),
            ],

            'datetime' => [
                'after'         => __('custom-fields::filament/resources/field.form.sections.form-settings.validations.datetime.after'),
                'afterOrEqual'  => __('custom-fields::filament/resources/field.form.sections.form-settings.validations.datetime.after-or-equal'),
                'before'        => __('custom-fields::filament/resources/field.form.sections.form-settings.validations.datetime.before'),
                'beforeOrEqual' => __('custom-fields::filament/resources/field.form.sections.form-settings.validations.datetime.before-or-equal'),
            ],

            'editor' => [
                'filled'    => __('custom-fields::filament/resources/field.form.sections.form-settings.validations.editor.filled'),
                'maxLength' => __('custom-fields::filament/resources/field.form.sections.form-settings.validations.editor.max-length'),
                'minLength' => __('custom-fields::filament/resources/field.form.sections.form-settings.validations.editor.min-length'),
            ],

            'markdown' => [
                'filled'    => __('custom-fields::filament/resources/field.form.sections.form-settings.validations.markdown.filled'),
                'maxLength' => __('custom-fields::filament/resources/field.form.sections.form-settings.validations.markdown.max-length'),
                'minLength' => __('custom-fields::filament/resources/field.form.sections.form-settings.validations.markdown.min-length'),
            ],

            'color' => [
                'hexColor' => __('custom-fields::filament/resources/field.form.sections.form-settings.validations.color.hex-color'),
            ],

            default => [],
        };

        return array_merge($typeValidations, $commonValidations);
    }

    public static function getTypeFormSettings(?string $type): array
    {
        if (is_null($type)) {
            return [];
        }

        return match ($type) {
            'text' => [
                'autocapitalize'  => __('custom-fields::filament/resources/field.form.sections.form-settings.settings.text.autocapitalize'),
                'autocomplete'    => __('custom-fields::filament/resources/field.form.sections.form-settings.settings.text.autocomplete'),
                'autofocus'       => __('custom-fields::filament/resources/field.form.sections.form-settings.settings.text.autofocus'),
                'default'         => __('custom-fields::filament/resources/field.form.sections.form-settings.settings.text.default'),
                'disabled'        => __('custom-fields::filament/resources/field.form.sections.form-settings.settings.text.disabled'),
                'helperText'      => __('custom-fields::filament/resources/field.form.sections.form-settings.settings.text.helper-text'),
                'hint'            => __('custom-fields::filament/resources/field.form.sections.form-settings.settings.text.hint'),
                'hintColor'       => __('custom-fields::filament/resources/field.form.sections.form-settings.settings.text.hint-color'),
                'hintIcon'        => __('custom-fields::filament/resources/field.form.sections.form-settings.settings.text.hint-icon'),
                'id'              => __('custom-fields::filament/resources/field.form.sections.form-settings.settings.text.id'),
                'inputMode'       => __('custom-fields::filament/resources/field.form.sections.form-settings.settings.text.input-mode'),
                'mask'            => __('custom-fields::filament/resources/field.form.sections.form-settings.settings.text.mask'),
                'placeholder'     => __('custom-fields::filament/resources/field.form.sections.form-settings.settings.text.placeholder'),
                'prefix'          => __('custom-fields::filament/resources/field.form.sections.form-settings.settings.text.prefix'),
                'prefixIcon'      => __('custom-fields::filament/resources/field.form.sections.form-settings.settings.text.prefix-icon'),
                'prefixIconColor' => __('custom-fields::filament/resources/field.form.sections.form-settings.settings.text.prefix-icon-color'),
                'readOnly'        => __('custom-fields::filament/resources/field.form.sections.form-settings.settings.text.read-only'),
                'step'            => __('custom-fields::filament/resources/field.form.sections.form-settings.settings.text.step'),
                'suffix'          => __('custom-fields::filament/resources/field.form.sections.form-settings.settings.text.suffix'),
                'suffixIcon'      => __('custom-fields::filament/resources/field.form.sections.form-settings.settings.text.suffix-icon'),
                'suffixIconColor' => __('custom-fields::filament/resources/field.form.sections.form-settings.settings.text.suffix-icon-color'),
            ],

            'textarea' => [
                'autofocus'   => __('custom-fields::filament/resources/field.form.sections.form-settings.settings.textarea.autofocus'),
                'autosize'    => __('custom-fields::filament/resources/field.form.sections.form-settings.settings.textarea.autosize'),
                'cols'        => __('custom-fields::filament/resources/field.form.sections.form-settings.settings.textarea.cols'),
                'default'     => __('custom-fields::filament/resources/field.form.sections.form-settings.settings.textarea.default'),
                'disabled'    => __('custom-fields::filament/resources/field.form.sections.form-settings.settings.textarea.disabled'),
                'helperText'  => __('custom-fields::filament/resources/field.form.sections.form-settings.settings.textarea.helper-text'),
                'hint'        => __('custom-fields::filament/resources/field.form.sections.form-settings.settings.textarea.hint'),
                'hintColor'   => __('custom-fields::filament/resources/field.form.sections.form-settings.settings.textarea.hint-color'),
                'hintIcon'    => __('custom-fields::filament/resources/field.form.sections.form-settings.settings.textarea.hinticon'),
                'id'          => __('custom-fields::filament/resources/field.form.sections.form-settings.settings.textarea.id'),
                'placeholder' => __('custom-fields::filament/resources/field.form.sections.form-settings.settings.textarea.placeholder'),
                'readOnly'    => __('custom-fields::filament/resources/field.form.sections.form-settings.settings.textarea.read-only'),
                'rows'        => __('custom-fields::filament/resources/field.form.sections.form-settings.settings.textarea.rows'),
            ],

            'select' => [
                'default'                => __('custom-fields::filament/resources/field.form.sections.form-settings.settings.select.default'),
                'disabled'               => __('custom-fields::filament/resources/field.form.sections.form-settings.settings.select.disabled'),
                'helperText'             => __('custom-fields::filament/resources/field.form.sections.form-settings.settings.select.helper-text'),
                'hint'                   => __('custom-fields::filament/resources/field.form.sections.form-settings.settings.select.hint'),
                'hintColor'              => __('custom-fields::filament/resources/field.form.sections.form-settings.settings.select.hint-color'),
                'hintIcon'               => __('custom-fields::filament/resources/field.form.sections.form-settings.settings.select.hint-icon'),
                'id'                     => __('custom-fields::filament/resources/field.form.sections.form-settings.settings.select.id'),
                'loadingMessage'         => __('custom-fields::filament/resources/field.form.sections.form-settings.settings.select.loading-message'),
                'noSearchResultsMessage' => __('custom-fields::filament/resources/field.form.sections.form-settings.settings.select.no-search-results-message'),
                'optionsLimit'           => __('custom-fields::filament/resources/field.form.sections.form-settings.settings.select.options-limit'),
                'preload'                => __('custom-fields::filament/resources/field.form.sections.form-settings.settings.select.preload'),
                'searchable'             => __('custom-fields::filament/resources/field.form.sections.form-settings.settings.select.searchable'),
                'searchDebounce'         => __('custom-fields::filament/resources/field.form.sections.form-settings.settings.select.search-debounce'),
                'searchingMessage'       => __('custom-fields::filament/resources/field.form.sections.form-settings.settings.select.searching-message'),
                'searchPrompt'           => __('custom-fields::filament/resources/field.form.sections.form-settings.settings.select.search-prompt'),
            ],

            'radio' => [
                'default'    => __('custom-fields::filament/resources/field.form.sections.form-settings.settings.radio.default'),
                'disabled'   => __('custom-fields::filament/resources/field.form.sections.form-settings.settings.radio.disabled'),
                'helperText' => __('custom-fields::filament/resources/field.form.sections.form-settings.settings.radio.helper-text'),
                'hint'       => __('custom-fields::filament/resources/field.form.sections.form-settings.settings.radio.hint'),
                'hintColor'  => __('custom-fields::filament/resources/field.form.sections.form-settings.settings.radio.hint-color'),
                'hintIcon'   => __('custom-fields::filament/resources/field.form.sections.form-settings.settings.radio.hint-icon'),
                'id'         => __('custom-fields::filament/resources/field.form.sections.form-settings.settings.radio.id'),
            ],

            'checkbox' => [
                'default'    => __('custom-fields::filament/resources/field.form.sections.form-settings.settings.checkbox.default'),
                'disabled'   => __('custom-fields::filament/resources/field.form.sections.form-settings.settings.checkbox.disabled'),
                'helperText' => __('custom-fields::filament/resources/field.form.sections.form-settings.settings.checkbox.helper-text'),
                'hint'       => __('custom-fields::filament/resources/field.form.sections.form-settings.settings.checkbox.hint'),
                'hintColor'  => __('custom-fields::filament/resources/field.form.sections.form-settings.settings.checkbox.hint-color'),
                'hintIcon'   => __('custom-fields::filament/resources/field.form.sections.form-settings.settings.checkbox.hint-icon'),
                'id'         => __('custom-fields::filament/resources/field.form.sections.form-settings.settings.checkbox.id'),
                'inline'     => __('custom-fields::filament/resources/field.form.sections.form-settings.settings.checkbox.inline'),
            ],

            'toggle' => [
                'default'    => __('custom-fields::filament/resources/field.form.sections.form-settings.settings.toggle.default'),
                'disabled'   => __('custom-fields::filament/resources/field.form.sections.form-settings.settings.toggle.disabled'),
                'helperText' => __('custom-fields::filament/resources/field.form.sections.form-settings.settings.toggle.helper-text'),
                'hint'       => __('custom-fields::filament/resources/field.form.sections.form-settings.settings.toggle.hint'),
                'hintColor'  => __('custom-fields::filament/resources/field.form.sections.form-settings.settings.toggle.hint-color'),
                'hintIcon'   => __('custom-fields::filament/resources/field.form.sections.form-settings.settings.toggle.hint-icon'),
                'id'         => __('custom-fields::filament/resources/field.form.sections.form-settings.settings.toggle.id'),
                'offColor'   => __('custom-fields::filament/resources/field.form.sections.form-settings.settings.toggle.off-color'),
                'offIcon'    => __('custom-fields::filament/resources/field.form.sections.form-settings.settings.toggle.off-icon'),
                'onColor'    => __('custom-fields::filament/resources/field.form.sections.form-settings.settings.toggle.on-color'),
                'onIcon'     => __('custom-fields::filament/resources/field.form.sections.form-settings.settings.toggle.on-icon'),
            ],

            'checkbox_list' => [
                'bulkToggleable'         => __('custom-fields::filament/resources/field.form.sections.form-settings.settings.checkbox-list.bulk-toggleable'),
                'columns'                => __('custom-fields::filament/resources/field.form.sections.form-settings.settings.checkbox-list.columns'),
                'default'                => __('custom-fields::filament/resources/field.form.sections.form-settings.settings.checkbox-list.default'),
                'disabled'               => __('custom-fields::filament/resources/field.form.sections.form-settings.settings.checkbox-list.disabled'),
                'gridDirection'          => __('custom-fields::filament/resources/field.form.sections.form-settings.settings.checkbox-list.grid-direction'),
                'helperText'             => __('custom-fields::filament/resources/field.form.sections.form-settings.settings.checkbox-list.helper-text'),
                'hint'                   => __('custom-fields::filament/resources/field.form.sections.form-settings.settings.checkbox-list.hint'),
                'hintColor'              => __('custom-fields::filament/resources/field.form.sections.form-settings.settings.checkbox-list.hint-color'),
                'hintIcon'               => __('custom-fields::filament/resources/field.form.sections.form-settings.settings.checkbox-list.hint-icon'),
                'id'                     => __('custom-fields::filament/resources/field.form.sections.form-settings.settings.checkbox-list.id'),
                'maxItems'               => __('custom-fields::filament/resources/field.form.sections.form-settings.settings.checkbox-list.max-items'),
                'minItems'               => __('custom-fields::filament/resources/field.form.sections.form-settings.settings.checkbox-list.min-items'),
                'noSearchResultsMessage' => __('custom-fields::filament/resources/field.form.sections.form-settings.settings.checkbox-list.no-search-results-message'),
                'searchable'             => __('custom-fields::filament/resources/field.form.sections.form-settings.settings.checkbox-list.searchable'),
            ],

            'datetime' => [
                'closeOnDateSelection'   => __('custom-fields::filament/resources/field.form.sections.form-settings.settings.datetime.close-on-date-selection'),
                'default'                => __('custom-fields::filament/resources/field.form.sections.form-settings.settings.datetime.default'),
                'disabled'               => __('custom-fields::filament/resources/field.form.sections.form-settings.settings.datetime.disabled'),
                'disabledDates'          => __('custom-fields::filament/resources/field.form.sections.form-settings.settings.datetime.disabled-dates'),
                'displayFormat'          => __('custom-fields::filament/resources/field.form.sections.form-settings.settings.datetime.display-format'),
                'firstDayOfWeek'         => __('custom-fields::filament/resources/field.form.sections.form-settings.settings.datetime.first-day-of-week'),
                'format'                 => __('custom-fields::filament/resources/field.form.sections.form-settings.settings.datetime.format'),
                'helperText'             => __('custom-fields::filament/resources/field.form.sections.form-settings.settings.datetime.helper-text'),
                'hint'                   => __('custom-fields::filament/resources/field.form.sections.form-settings.settings.datetime.hint'),
                'hintColor'              => __('custom-fields::filament/resources/field.form.sections.form-settings.settings.datetime.hint-color'),
                'hintIcon'               => __('custom-fields::filament/resources/field.form.sections.form-settings.settings.datetime.hint-icon'),
                'hoursStep'              => __('custom-fields::filament/resources/field.form.sections.form-settings.settings.datetime.hours-step'),
                'id'                     => __('custom-fields::filament/resources/field.form.sections.form-settings.settings.datetime.id'),
                'locale'                 => __('custom-fields::filament/resources/field.form.sections.form-settings.settings.datetime.locale'),
                'minutesStep'            => __('custom-fields::filament/resources/field.form.sections.form-settings.settings.datetime.minutes-step'),
                'seconds'                => __('custom-fields::filament/resources/field.form.sections.form-settings.settings.datetime.seconds'),
                'secondsStep'            => __('custom-fields::filament/resources/field.form.sections.form-settings.settings.datetime.seconds-step'),
                'timezone'               => __('custom-fields::filament/resources/field.form.sections.form-settings.settings.datetime.timezone'),
                'weekStartsOnMonday'     => __('custom-fields::filament/resources/field.form.sections.form-settings.settings.datetime.week-starts-on-monday'),
                'weekStartsOnSunday'     => __('custom-fields::filament/resources/field.form.sections.form-settings.settings.datetime.week-starts-on-sunday'),
            ],

            'editor' => [
                'default'     => __('custom-fields::filament/resources/field.form.sections.form-settings.settings.editor.default'),
                'disabled'    => __('custom-fields::filament/resources/field.form.sections.form-settings.settings.editor.disabled'),
                'helperText'  => __('custom-fields::filament/resources/field.form.sections.form-settings.settings.editor.helper-text'),
                'hint'        => __('custom-fields::filament/resources/field.form.sections.form-settings.settings.editor.hint'),
                'hintColor'   => __('custom-fields::filament/resources/field.form.sections.form-settings.settings.editor.hint-color'),
                'hintIcon'    => __('custom-fields::filament/resources/field.form.sections.form-settings.settings.editor.hint-icon'),
                'id'          => __('custom-fields::filament/resources/field.form.sections.form-settings.settings.editor.id'),
                'placeholder' => __('custom-fields::filament/resources/field.form.sections.form-settings.settings.editor.placeholder'),
                'readOnly'    => __('custom-fields::filament/resources/field.form.sections.form-settings.settings.editor.read-only'),
            ],

            'markdown' => [
                'default'     => __('custom-fields::filament/resources/field.form.sections.form-settings.settings.markdown.default'),
                'disabled'    => __('custom-fields::filament/resources/field.form.sections.form-settings.settings.markdown.disabled'),
                'helperText'  => __('custom-fields::filament/resources/field.form.sections.form-settings.settings.markdown.helper-text'),
                'hint'        => __('custom-fields::filament/resources/field.form.sections.form-settings.settings.markdown.hint'),
                'hintColor'   => __('custom-fields::filament/resources/field.form.sections.form-settings.settings.markdown.hint-color'),
                'hintIcon'    => __('custom-fields::filament/resources/field.form.sections.form-settings.settings.markdown.hint-icon'),
                'id'          => __('custom-fields::filament/resources/field.form.sections.form-settings.settings.markdown.id'),
                'placeholder' => __('custom-fields::filament/resources/field.form.sections.form-settings.settings.markdown.placeholder'),
                'readOnly'    => __('custom-fields::filament/resources/field.form.sections.form-settings.settings.markdown.read-only'),
            ],

            'color' => [
                'default'    => __('custom-fields::filament/resources/field.form.sections.form-settings.settings.color.default'),
                'disabled'   => __('custom-fields::filament/resources/field.form.sections.form-settings.settings.color.disabled'),
                'helperText' => __('custom-fields::filament/resources/field.form.sections.form-settings.settings.color.helper-text'),
                'hint'       => __('custom-fields::filament/resources/field.form.sections.form-settings.settings.color.hint'),
                'hintColor'  => __('custom-fields::filament/resources/field.form.sections.form-settings.settings.color.hint-color'),
                'hintIcon'   => __('custom-fields::filament/resources/field.form.sections.form-settings.settings.color.hint-icon'),
                'hsl'        => __('custom-fields::filament/resources/field.form.sections.form-settings.settings.color.hsl'),
                'id'         => __('custom-fields::filament/resources/field.form.sections.form-settings.settings.color.id'),
                'rgb'        => __('custom-fields::filament/resources/field.form.sections.form-settings.settings.color.rgb'),
                'rgba'       => __('custom-fields::filament/resources/field.form.sections.form-settings.settings.color.rgba'),
            ],

            'file' => [
                'acceptedFileTypes'                => __('custom-fields::filament/resources/field.form.sections.form-settings.settings.file.accepted-file-types'),
                'appendFiles'                      => __('custom-fields::filament/resources/field.form.sections.form-settings.settings.file.append-files'),
                'deletable'                        => __('custom-fields::filament/resources/field.form.sections.form-settings.settings.file.deletable'),
                'directory'                        => __('custom-fields::filament/resources/field.form.sections.form-settings.settings.file.directory'),
                'downloadable'                     => __('custom-fields::filament/resources/field.form.sections.form-settings.settings.file.downloadable'),
                'fetchFileInformation'             => __('custom-fields::filament/resources/field.form.sections.form-settings.settings.file.fetch-file-information'),
                'fileAttachmentsDirectory'         => __('custom-fields::filament/resources/field.form.sections.form-settings.settings.file.file-attachment-directory'),
                'fileAttachmentsVisibility'        => __('custom-fields::filament/resources/field.form.sections.form-settings.settings.file.file-attachments-visibility'),
                'image'                            => __('custom-fields::filament/resources/field.form.sections.form-settings.settings.file.image'),
                'imageCropAspectRatio'             => __('custom-fields::filament/resources/field.form.sections.form-settings.settings.file.image-crop-aspect-ratio'),
                'imageEditor'                      => __('custom-fields::filament/resources/field.form.sections.form-settings.settings.file.image-editor'),
                'imageEditorAspectRatios'          => __('custom-fields::filament/resources/field.form.sections.form-settings.settings.file.image-editor-aspect-ratios'),
                'imageEditorEmptyFillColor'        => __('custom-fields::filament/resources/field.form.sections.form-settings.settings.file.image-editor-empty-fill-color'),
                'imageEditorMode'                  => __('custom-fields::filament/resources/field.form.sections.form-settings.settings.file.image-editor-mode'),
                'imagePreviewHeight'               => __('custom-fields::filament/resources/field.form.sections.form-settings.settings.file.image-preview-height'),
                'imageResizeMode'                  => __('custom-fields::filament/resources/field.form.sections.form-settings.settings.file.image-resize-mode'),
                'imageResizeTargetHeight'          => __('custom-fields::filament/resources/field.form.sections.form-settings.settings.file.image-resize-target-height'),
                'imageResizeTargetWidth'           => __('custom-fields::filament/resources/field.form.sections.form-settings.settings.file.image-resize-target-width'),
                'loadingIndicatorPosition'         => __('custom-fields::filament/resources/field.form.sections.form-settings.settings.file.loading-indicator-position'),
                'moveFiles'                        => __('custom-fields::filament/resources/field.form.sections.form-settings.settings.file.move-files'),
                'openable'                         => __('custom-fields::filament/resources/field.form.sections.form-settings.settings.file.openable'),
                'orientImagesFromExif'             => __('custom-fields::filament/resources/field.form.sections.form-settings.settings.file.orient-images-from-exif'),
                'panelAspectRatio'                 => __('custom-fields::filament/resources/field.form.sections.form-settings.settings.file.panel-aspect-ratio'),
                'panelLayout'                      => __('custom-fields::filament/resources/field.form.sections.form-settings.settings.file.panel-layout'),
                'previewable'                      => __('custom-fields::filament/resources/field.form.sections.form-settings.settings.file.previewable'),
                'removeUploadedFileButtonPosition' => __('custom-fields::filament/resources/field.form.sections.form-settings.settings.file.remove-uploaded-file-button-position'),
                'reorderable'                      => __('custom-fields::filament/resources/field.form.sections.form-settings.settings.file.reorderable'),
                'storeFiles'                       => __('custom-fields::filament/resources/field.form.sections.form-settings.settings.file.store-files'),
                'uploadButtonPosition'             => __('custom-fields::filament/resources/field.form.sections.form-settings.settings.file.upload-button-position'),
                'uploadingMessage'                 => __('custom-fields::filament/resources/field.form.sections.form-settings.settings.file.uploading-message'),
                'uploadProgressIndicatorPosition'  => __('custom-fields::filament/resources/field.form.sections.form-settings.settings.file.upload-progress-indicator-position'),
                'visibility'                       => __('custom-fields::filament/resources/field.form.sections.form-settings.settings.file.visibility'),
            ],
        };
    }

    public static function getTableSettingsSchema(): array
    {
        return [
            Toggle::make('use_in_table')
                ->label(__('custom-fields::filament/resources/field.form.sections.table-settings.fields.use-in-table'))
                ->required()
                ->live(),
            Repeater::make('table_settings')
                ->hiddenLabel()
                ->visible(fn (Get $get): bool => $get('use_in_table'))
                ->schema([
                    Select::make('setting')
                        ->label(__('custom-fields::filament/resources/field.form.sections.table-settings.fields.setting'))
                        ->searchable()
                        ->required()
                        ->distinct()
                        ->live()
                        ->options(fn (Get $get): array => static::getTypeTableSettings($get('../../type'))),
                    TextInput::make('value')
                        ->label(__('custom-fields::filament/resources/field.form.sections.table-settings.fields.value'))
                        ->required()
                        ->maxLength(255)
                        ->visible(fn (Get $get): bool => in_array($get('setting'), [
                            'copyMessage',
                            'dateTimeTooltip',
                            'default',
                            'icon',
                            'label',
                            'money',
                            'placeholder',
                            'prefix',
                            'suffix',
                            'tooltip',
                            'width',
                        ])),

                    Select::make('value')
                        ->label(__('custom-fields::filament/resources/field.form.sections.table-settings.fields.color'))
                        ->required()
                        ->visible(fn (Get $get): bool => in_array($get('setting'), [
                            'color',
                            'iconColor',
                        ]))
                        ->options([
                            'danger'    => __('custom-fields::filament/resources/field.form.sections.table-settings.fields.color-options.danger'),
                            'info'      => __('custom-fields::filament/resources/field.form.sections.table-settings.fields.color-options.info'),
                            'primary'   => __('custom-fields::filament/resources/field.form.sections.table-settings.fields.color-options.primary'),
                            'secondary' => __('custom-fields::filament/resources/field.form.sections.table-settings.fields.color-options.secondary'),
                            'warning'   => __('custom-fields::filament/resources/field.form.sections.table-settings.fields.color-options.warning'),
                            'success'   => __('custom-fields::filament/resources/field.form.sections.table-settings.fields.color-options.success'),
                        ]),

                    Select::make('value')
                        ->label(__('custom-fields::filament/resources/field.form.sections.table-settings.fields.alignment'))
                        ->required()
                        ->visible(fn (Get $get): bool => in_array($get('setting'), [
                            'alignment',
                            'verticalAlignment',
                        ]))
                        ->options([
                            Alignment::Start->value   => __('custom-fields::filament/resources/field.form.sections.table-settings.fields.alignment-options.start'),
                            Alignment::Left->value    => __('custom-fields::filament/resources/field.form.sections.table-settings.fields.alignment-options.left'),
                            Alignment::Center->value  => __('custom-fields::filament/resources/field.form.sections.table-settings.fields.alignment-options.center'),
                            Alignment::End->value     => __('custom-fields::filament/resources/field.form.sections.table-settings.fields.alignment-options.end'),
                            Alignment::Right->value   => __('custom-fields::filament/resources/field.form.sections.table-settings.fields.alignment-options.right'),
                            Alignment::Justify->value => __('custom-fields::filament/resources/field.form.sections.table-settings.fields.alignment-options.justify'),
                            Alignment::Between->value => __('custom-fields::filament/resources/field.form.sections.table-settings.fields.alignment-options.between'),
                        ]),

                    Select::make('value')
                        ->label(__('custom-fields::filament/resources/field.form.sections.table-settings.fields.font-weight'))
                        ->required()
                        ->visible(fn (Get $get): bool => in_array($get('setting'), [
                            'weight',
                        ]))
                        ->options([
                            FontWeight::Thin->name       => __('custom-fields::filament/resources/field.form.sections.table-settings.fields.font-weight-options.thin'),
                            FontWeight::ExtraLight->name => __('custom-fields::filament/resources/field.form.sections.table-settings.fields.font-weight-options.extra-light'),
                            FontWeight::Light->name      => __('custom-fields::filament/resources/field.form.sections.table-settings.fields.font-weight-options.light'),
                            FontWeight::Normal->name     => __('custom-fields::filament/resources/field.form.sections.table-settings.fields.font-weight-options.normal'),
                            FontWeight::Medium->name     => __('custom-fields::filament/resources/field.form.sections.table-settings.fields.font-weight-options.medium'),
                            FontWeight::SemiBold->name   => __('custom-fields::filament/resources/field.form.sections.table-settings.fields.font-weight-options.semi-bold'),
                            FontWeight::Bold->name       => __('custom-fields::filament/resources/field.form.sections.table-settings.fields.font-weight-options.bold'),
                            FontWeight::ExtraBold->name  => __('custom-fields::filament/resources/field.form.sections.table-settings.fields.font-weight-options.extra-bold'),
                            FontWeight::Black->name      => __('custom-fields::filament/resources/field.form.sections.table-settings.fields.font-weight-options.black'),
                        ]),

                    Select::make('value')
                        ->label(__('custom-fields::filament/resources/field.form.sections.table-settings.fields.icon-position'))
                        ->required()
                        ->visible(fn (Get $get): bool => in_array($get('setting'), [
                            'iconPosition',
                        ]))
                        ->options([
                            IconPosition::Before->value => __('custom-fields::filament/resources/field.form.sections.table-settings.fields.icon-position-options.before'),
                            IconPosition::After->value  => __('custom-fields::filament/resources/field.form.sections.table-settings.fields.icon-position-options.after'),
                        ]),

                    Select::make('value')
                        ->label(__('custom-fields::filament/resources/field.form.sections.table-settings.fields.size'))
                        ->required()
                        ->visible(fn (Get $get): bool => in_array($get('setting'), [
                            'size',
                        ]))
                        ->options([
                            TextSize::ExtraSmall->name  => __('custom-fields::filament/resources/field.form.sections.table-settings.fields.size-options.extra-small'),
                            TextSize::Small->name       => __('custom-fields::filament/resources/field.form.sections.table-settings.fields.size-options.small'),
                            TextSize::Medium->name      => __('custom-fields::filament/resources/field.form.sections.table-settings.fields.size-options.medium'),
                            TextSize::Large->name       => __('custom-fields::filament/resources/field.form.sections.table-settings.fields.size-options.large'),
                        ]),

                    TextInput::make('value')
                        ->label(__('custom-fields::filament/resources/field.form.sections.table-settings.fields.value'))
                        ->required()
                        ->numeric()
                        ->minValue(0)
                        ->maxValue(99999999999)
                        ->visible(fn (Get $get): bool => in_array($get('setting'), [
                            'limit',
                            'words',
                            'lineClamp',
                            'copyMessageDuration',
                        ])),
                ])
                ->addActionLabel(__('custom-fields::filament/resources/field.form.sections.table-settings.fields.add-setting'))
                ->columns(2)
                ->collapsible()
                ->itemLabel(function (array $state, Get $get): ?string {
                    $settings = static::getTypeTableSettings($get('type'));

                    return $settings[$state['setting']] ?? null;
                }),
        ];
    }

    public static function getTypeTableSettings(?string $type): array
    {
        if (is_null($type)) {
            return [];
        }

        $commonSettings = [
            'alignEnd'             => __('custom-fields::filament/resources/field.form.sections.table-settings.settings.common.align-end'),
            'alignment'            => __('custom-fields::filament/resources/field.form.sections.table-settings.settings.common.alignment'),
            'alignStart'           => __('custom-fields::filament/resources/field.form.sections.table-settings.settings.common.align-start'),
            'badge'                => __('custom-fields::filament/resources/field.form.sections.table-settings.settings.common.badge'),
            'boolean'              => __('custom-fields::filament/resources/field.form.sections.table-settings.settings.common.boolean'),
            'color'                => __('custom-fields::filament/resources/field.form.sections.table-settings.settings.common.color'),
            'copyable'             => __('custom-fields::filament/resources/field.form.sections.table-settings.settings.common.copyable'),
            'copyMessage'          => __('custom-fields::filament/resources/field.form.sections.table-settings.settings.common.copy-message'),
            'copyMessageDuration'  => __('custom-fields::filament/resources/field.form.sections.table-settings.settings.common.copy-message-duration'),
            'default'              => __('custom-fields::filament/resources/field.form.sections.table-settings.settings.common.default'),
            'filterable'           => __('custom-fields::filament/resources/field.form.sections.table-settings.settings.common.filterable'),
            'groupable'            => __('custom-fields::filament/resources/field.form.sections.table-settings.settings.common.groupable'),
            'grow'                 => __('custom-fields::filament/resources/field.form.sections.table-settings.settings.common.grow'),
            'icon'                 => __('custom-fields::filament/resources/field.form.sections.table-settings.settings.common.icon'),
            'iconColor'            => __('custom-fields::filament/resources/field.form.sections.table-settings.settings.common.icon-color'),
            'iconPosition'         => __('custom-fields::filament/resources/field.form.sections.table-settings.settings.common.icon-position'),
            'label'                => __('custom-fields::filament/resources/field.form.sections.table-settings.settings.common.label'),
            'limit'                => __('custom-fields::filament/resources/field.form.sections.table-settings.settings.common.limit'),
            'lineClamp'            => __('custom-fields::filament/resources/field.form.sections.table-settings.settings.common.line-clamp'),
            'money'                => __('custom-fields::filament/resources/field.form.sections.table-settings.settings.common.money'),
            'placeholder'          => __('custom-fields::filament/resources/field.form.sections.table-settings.settings.common.placeholder'),
            'prefix'               => __('custom-fields::filament/resources/field.form.sections.table-settings.settings.common.prefix'),
            'searchable'           => __('custom-fields::filament/resources/field.form.sections.table-settings.settings.common.searchable'),
            'size'                 => __('custom-fields::filament/resources/field.form.sections.table-settings.settings.common.size'),
            'sortable'             => __('custom-fields::filament/resources/field.form.sections.table-settings.settings.common.sortable'),
            'suffix'               => __('custom-fields::filament/resources/field.form.sections.table-settings.settings.common.suffix'),
            'toggleable'           => __('custom-fields::filament/resources/field.form.sections.table-settings.settings.common.toggleable'),
            'tooltip'              => __('custom-fields::filament/resources/field.form.sections.table-settings.settings.common.tooltip'),
            'verticalAlignment'    => __('custom-fields::filament/resources/field.form.sections.table-settings.settings.common.vertical-alignment'),
            'verticallyAlignStart' => __('custom-fields::filament/resources/field.form.sections.table-settings.settings.common.vertically-align-start'),
            'weight'               => __('custom-fields::filament/resources/field.form.sections.table-settings.settings.common.weight'),
            'width'                => __('custom-fields::filament/resources/field.form.sections.table-settings.settings.common.width'),
            'words'                => __('custom-fields::filament/resources/field.form.sections.table-settings.settings.common.words'),
            'wrapHeader'           => __('custom-fields::filament/resources/field.form.sections.table-settings.settings.common.wrap-header'),
        ];

        $typeSettings = match ($type) {
            'datetime' => [
                'date'            => __('custom-fields::filament/resources/field.form.sections.table-settings.settings.datetime.date'),
                'dateTime'        => __('custom-fields::filament/resources/field.form.sections.table-settings.settings.datetime.date-time'),
                'dateTimeTooltip' => __('custom-fields::filament/resources/field.form.sections.table-settings.settings.datetime.date-time-tooltip'),
                'since'           => __('custom-fields::filament/resources/field.form.sections.table-settings.settings.datetime.since'),
            ],

            default => [],
        };

        return array_merge($typeSettings, $commonSettings);
    }

    public static function getInfolistSettingsSchema(): array
    {
        return [
            Repeater::make('infolist_settings')
                ->hiddenLabel()
                ->schema([
                    Select::make('setting')
                        ->label(__('custom-fields::filament/resources/field.form.sections.infolist-settings.fields.setting'))
                        ->searchable()
                        ->required()
                        ->distinct()
                        ->live()
                        ->options(fn (Get $get): array => static::getTypeInfolistSettings($get('../../type'))),
                    TextInput::make('value')
                        ->label(__('custom-fields::filament/resources/field.form.sections.infolist-settings.fields.value'))
                        ->required()
                        ->maxLength(255)
                        ->visible(fn (Get $get): bool => in_array($get('setting'), [
                            'copyMessage',
                            'dateTimeTooltip',
                            'default',
                            'icon',
                            'label',
                            'money',
                            'placeholder',
                            'tooltip',
                            'helperText',
                            'hint',
                            'hintIcon',
                            'separator',
                            'trueIcon',
                            'falseIcon',
                        ])),

                    Select::make('value')
                        ->label(__('custom-fields::filament/resources/field.form.sections.infolist-settings.fields.color'))
                        ->required()
                        ->visible(fn (Get $get): bool => in_array($get('setting'), [
                            'color',
                            'iconColor',
                            'hintColor',
                            'trueColor',
                            'falseColor',
                        ]))
                        ->options([
                            'danger'    => __('custom-fields::filament/resources/field.form.sections.infolist-settings.fields.color-options.danger'),
                            'info'      => __('custom-fields::filament/resources/field.form.sections.infolist-settings.fields.color-options.info'),
                            'primary'   => __('custom-fields::filament/resources/field.form.sections.infolist-settings.fields.color-options.primary'),
                            'secondary' => __('custom-fields::filament/resources/field.form.sections.infolist-settings.fields.color-options.secondary'),
                            'warning'   => __('custom-fields::filament/resources/field.form.sections.infolist-settings.fields.color-options.warning'),
                            'success'   => __('custom-fields::filament/resources/field.form.sections.infolist-settings.fields.color-options.success'),
                        ]),

                    Select::make('value')
                        ->label(__('custom-fields::filament/resources/field.form.sections.infolist-settings.fields.font-weight'))
                        ->required()
                        ->visible(fn (Get $get): bool => in_array($get('setting'), [
                            'weight',
                        ]))
                        ->options([
                            FontWeight::Thin->name       => __('custom-fields::filament/resources/field.form.sections.infolist-settings.fields.font-weight-options.thin'),
                            FontWeight::ExtraLight->name => __('custom-fields::filament/resources/field.form.sections.infolist-settings.fields.font-weight-options.extra-light'),
                            FontWeight::Light->name      => __('custom-fields::filament/resources/field.form.sections.infolist-settings.fields.font-weight-options.light'),
                            FontWeight::Normal->name     => __('custom-fields::filament/resources/field.form.sections.infolist-settings.fields.font-weight-options.normal'),
                            FontWeight::Medium->name     => __('custom-fields::filament/resources/field.form.sections.infolist-settings.fields.font-weight-options.medium'),
                            FontWeight::SemiBold->name   => __('custom-fields::filament/resources/field.form.sections.infolist-settings.fields.font-weight-options.semi-bold'),
                            FontWeight::Bold->name       => __('custom-fields::filament/resources/field.form.sections.infolist-settings.fields.font-weight-options.bold'),
                            FontWeight::ExtraBold->name  => __('custom-fields::filament/resources/field.form.sections.infolist-settings.fields.font-weight-options.extra-bold'),
                            FontWeight::Black->name      => __('custom-fields::filament/resources/field.form.sections.infolist-settings.fields.font-weight-options.black'),
                        ]),

                    Select::make('value')
                        ->label(__('custom-fields::filament/resources/field.form.sections.infolist-settings.fields.icon-position'))
                        ->required()
                        ->visible(fn (Get $get): bool => in_array($get('setting'), [
                            'iconPosition',
                        ]))
                        ->options([
                            IconPosition::Before->value => __('custom-fields::filament/resources/field.form.sections.infolist-settings.fields.icon-position-options.before'),
                            IconPosition::After->value  => __('custom-fields::filament/resources/field.form.sections.infolist-settings.fields.icon-position-options.after'),
                        ]),

                    Select::make('value')
                        ->label(__('custom-fields::filament/resources/field.form.sections.infolist-settings.fields.size'))
                        ->required()
                        ->visible(fn (Get $get): bool => in_array($get('setting'), [
                            'size',
                        ]))
                        ->options([
                            TextSize::Small->name  => __('custom-fields::filament/resources/field.form.sections.infolist-settings.fields.size-options.small'),
                            TextSize::Medium->name => __('custom-fields::filament/resources/field.form.sections.infolist-settings.fields.size-options.medium'),
                            TextSize::Large->name  => __('custom-fields::filament/resources/field.form.sections.infolist-settings.fields.size-options.large'),
                        ]),

                    TextInput::make('value')
                        ->label(__('custom-fields::filament/resources/field.form.sections.infolist-settings.fields.value'))
                        ->required()
                        ->numeric()
                        ->minValue(0)
                        ->maxValue(99999999999)
                        ->visible(fn (Get $get): bool => in_array($get('setting'), [
                            'limit',
                            'words',
                            'lineClamp',
                            'copyMessageDuration',
                            'columnSpan',
                            'limitList',
                        ])),
                ])
                ->addActionLabel(__('custom-fields::filament/resources/field.form.sections.infolist-settings.fields.add-setting'))
                ->columns(2)
                ->collapsible()
                ->itemLabel(function (array $state, Get $get): ?string {
                    $settings = static::getTypeInfolistSettings($get('type'));

                    return $settings[$state['setting']] ?? null;
                }),
        ];
    }

    public static function getTypeInfolistSettings(?string $type): array
    {
        if (is_null($type)) {
            return [];
        }

        $commonSettings = [
            'badge'               => __('custom-fields::filament/resources/field.form.sections.infolist-settings.settings.common.badge'),
            'color'               => __('custom-fields::filament/resources/field.form.sections.infolist-settings.settings.common.color'),
            'copyable'            => __('custom-fields::filament/resources/field.form.sections.infolist-settings.settings.common.copyable'),
            'copyMessage'         => __('custom-fields::filament/resources/field.form.sections.infolist-settings.settings.common.copy-message'),
            'copyMessageDuration' => __('custom-fields::filament/resources/field.form.sections.infolist-settings.settings.common.copy-message-duration'),
            'default'             => __('custom-fields::filament/resources/field.form.sections.infolist-settings.settings.common.default'),
            'icon'                => __('custom-fields::filament/resources/field.form.sections.infolist-settings.settings.common.icon'),
            'iconColor'           => __('custom-fields::filament/resources/field.form.sections.infolist-settings.settings.common.icon-color'),
            'iconPosition'        => __('custom-fields::filament/resources/field.form.sections.infolist-settings.settings.common.icon-position'),
            'label'               => __('custom-fields::filament/resources/field.form.sections.infolist-settings.settings.common.label'),
            'limit'               => __('custom-fields::filament/resources/field.form.sections.infolist-settings.settings.common.limit'),
            'lineClamp'           => __('custom-fields::filament/resources/field.form.sections.infolist-settings.settings.common.line-clamp'),
            'money'               => __('custom-fields::filament/resources/field.form.sections.infolist-settings.settings.common.money'),
            'placeholder'         => __('custom-fields::filament/resources/field.form.sections.infolist-settings.settings.common.placeholder'),
            'size'                => __('custom-fields::filament/resources/field.form.sections.infolist-settings.settings.common.size'),
            'tooltip'             => __('custom-fields::filament/resources/field.form.sections.infolist-settings.settings.common.tooltip'),
            'weight'              => __('custom-fields::filament/resources/field.form.sections.infolist-settings.settings.common.weight'),
            'words'               => __('custom-fields::filament/resources/field.form.sections.infolist-settings.settings.common.words'),
            'columnSpan'          => __('custom-fields::filament/resources/field.form.sections.infolist-settings.settings.common.column-span'),
            'helperText'          => __('custom-fields::filament/resources/field.form.sections.infolist-settings.settings.common.helper-text'),
            'hint'                => __('custom-fields::filament/resources/field.form.sections.infolist-settings.settings.common.hint'),
            'hintColor'           => __('custom-fields::filament/resources/field.form.sections.infolist-settings.settings.common.hint-color'),
            'hintIcon'            => __('custom-fields::filament/resources/field.form.sections.infolist-settings.settings.common.hint-icon'),
        ];

        $typeSettings = match ($type) {
            'datetime' => [
                'date'            => __('custom-fields::filament/resources/field.form.sections.infolist-settings.settings.datetime.date'),
                'dateTime'        => __('custom-fields::filament/resources/field.form.sections.infolist-settings.settings.datetime.date-time'),
                'dateTimeTooltip' => __('custom-fields::filament/resources/field.form.sections.infolist-settings.settings.datetime.date-time-tooltip'),
                'since'           => __('custom-fields::filament/resources/field.form.sections.infolist-settings.settings.datetime.since'),
            ],

            'checkbox_list' => [
                'separator'             => __('custom-fields::filament/resources/field.form.sections.infolist-settings.settings.checkbox-list.separator'),
                'listWithLineBreaks'    => __('custom-fields::filament/resources/field.form.sections.infolist-settings.settings.checkbox-list.list-with-line-breaks'),
                'bulleted'              => __('custom-fields::filament/resources/field.form.sections.infolist-settings.settings.checkbox-list.bulleted'),
                'limitList'             => __('custom-fields::filament/resources/field.form.sections.infolist-settings.settings.checkbox-list.limit-list'),
                'expandableLimitedList' => __('custom-fields::filament/resources/field.form.sections.infolist-settings.settings.checkbox-list.expandable-limited-list'),
            ],

            'select' => [
                'separator'             => __('custom-fields::filament/resources/field.form.sections.infolist-settings.settings.select.separator'),
                'listWithLineBreaks'    => __('custom-fields::filament/resources/field.form.sections.infolist-settings.settings.select.list-with-line-breaks'),
                'bulleted'              => __('custom-fields::filament/resources/field.form.sections.infolist-settings.settings.select.bulleted'),
                'limitList'             => __('custom-fields::filament/resources/field.form.sections.infolist-settings.settings.select.limit-list'),
                'expandableLimitedList' => __('custom-fields::filament/resources/field.form.sections.infolist-settings.settings.select.expandable-limited-list'),
            ],

            'checkbox' => [
                'boolean'    => __('custom-fields::filament/resources/field.form.sections.infolist-settings.settings.checkbox.boolean'),
                'falseIcon'  => __('custom-fields::filament/resources/field.form.sections.infolist-settings.settings.checkbox.false-icon'),
                'trueIcon'   => __('custom-fields::filament/resources/field.form.sections.infolist-settings.settings.checkbox.true-icon'),
                'trueColor'  => __('custom-fields::filament/resources/field.form.sections.infolist-settings.settings.checkbox.true-color'),
                'falseColor' => __('custom-fields::filament/resources/field.form.sections.infolist-settings.settings.checkbox.false-color'),
            ],

            'toggle' => [
                'boolean'    => __('custom-fields::filament/resources/field.form.sections.infolist-settings.settings.toggle.boolean'),
                'falseIcon'  => __('custom-fields::filament/resources/field.form.sections.infolist-settings.settings.toggle.false-icon'),
                'trueIcon'   => __('custom-fields::filament/resources/field.form.sections.infolist-settings.settings.toggle.true-icon'),
                'trueColor'  => __('custom-fields::filament/resources/field.form.sections.infolist-settings.settings.toggle.true-color'),
                'falseColor' => __('custom-fields::filament/resources/field.form.sections.infolist-settings.settings.toggle.false-color'),
            ],

            default => [],
        };

        return array_merge($typeSettings, $commonSettings);
    }
}
