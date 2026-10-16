<?php

namespace App\Filament\Resources\Delivery;

use App\Filament\Resources\Delivery\Pages\CreateDeliveryZone;
use App\Filament\Resources\Delivery\Pages\EditDeliveryZone;
use App\Filament\Resources\Delivery\Pages\ListDeliveryZones;
use App\Filament\Resources\Delivery\Schemas\DeliveryZoneForm;
use App\Filament\Resources\Delivery\Tables\DeliveryZonesTable;
use App\Models\DeliveryZone;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use UnitEnum;

class DeliveryZoneResource extends Resource
{
    protected static ?string $model = DeliveryZone::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-map';

    protected static string|UnitEnum|null $navigationGroup = 'Operations';

    protected static ?string $navigationLabel = 'Delivery Zones';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return DeliveryZoneForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DeliveryZonesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDeliveryZones::route('/'),
            'create' => CreateDeliveryZone::route('/create'),
            'edit' => EditDeliveryZone::route('/{record}/edit'),
        ];
    }
}