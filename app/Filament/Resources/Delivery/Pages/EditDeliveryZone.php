<?php

namespace App\Filament\Resources\Delivery\Pages;

use App\Filament\Resources\Delivery\DeliveryZoneResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditDeliveryZone extends EditRecord
{
    protected static string $resource = DeliveryZoneResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}