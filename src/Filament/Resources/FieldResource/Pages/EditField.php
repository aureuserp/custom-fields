<?php

namespace Webkul\CustomFields\Filament\Resources\FieldResource\Pages;

use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Webkul\CustomFields\CustomCustomFieldsColumnManager;
use Webkul\CustomFields\Filament\Resources\FieldResource;

class EditField extends EditRecord
{
    protected static string $resource = FieldResource::class;

    protected function getSavedNotification(): Notification
    {
        return Notification::make()
            ->success()
            ->title(__('custom-fields::filament/resources/field/pages/edit-field.notification.title'))
            ->body(__('custom-fields::filament/resources/field/pages/edit-field.notification.body'));
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function afterSave(): void
    {
        CustomFieldsColumnManager::updateColumn($this->record);
    }

    protected function getRedirectUrl(): string
    {
        return $this->previousUrl ?? $this->getResource()::getUrl('index');
    }
}
