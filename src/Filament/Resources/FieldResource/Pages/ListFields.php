<?php

namespace Webkul\CustomFields\Filament\Resources\FieldResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Webkul\CustomFields\Filament\Resources\FieldResource;
use Webkul\CustomFields\Models\Field;

class ListFields extends ListRecords
{
    protected static string $resource = FieldResource::class;

    public function getTabs(): array
    {
        return [
            'all' => Tab::make(__('custom-fields::filament/resources/field/pages/list-fields.tabs.all'))
                ->badge(Field::count()),
            'archived' => Tab::make(__('custom-fields::filament/resources/field/pages/list-fields.tabs.archived'))
                ->badge(Field::onlyTrashed()->count())
                ->modifyQueryUsing(function ($query) {
                    return $query->onlyTrashed();
                }),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label(__('custom-fields::filament/resources/field/pages/list-fields.header-actions.create.label'))
                ->icon('heroicon-o-plus-circle'),
        ];
    }
}
