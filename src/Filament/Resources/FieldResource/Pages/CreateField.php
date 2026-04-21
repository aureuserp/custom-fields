<?php

namespace Webkul\CustomFields\Filament\Resources\FieldResource\Pages;

use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Webkul\CustomFields\CustomFieldsColumnManager;
use Webkul\CustomFields\Filament\Resources\FieldResource;

class CreateField extends CreateRecord
{
    protected static string $resource = FieldResource::class;

    protected function getCreatedNotification(): Notification
    {
        return Notification::make()
            ->success()
            ->title(__('custom-fields::filament/resources/field/pages/create-field.notification.title'))
            ->body(__('custom-fields::filament/resources/field/pages/create-field.notification.body'));
    }

    protected function afterCreate(): void
    {
        CustomFieldsColumnManager::createColumn($this->record);
    }

    protected function getRedirectUrl(): string
    {
        return $this->previousUrl ?? $this->getResource()::getUrl('index');
    }
}
