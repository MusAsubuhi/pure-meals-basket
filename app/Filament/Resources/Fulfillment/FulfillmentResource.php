<?php

namespace App\Filament\Resources\Fulfillment;

use App\Filament\Resources\Fulfillment\Pages\ListFulfillments;
use App\Filament\Resources\Fulfillment\RelationManagers\FulfillmentEventsRelationManager;
use App\Filament\Resources\Fulfillment\Schemas\FulfillmentForm;
use App\Filament\Resources\Fulfillment\Tables\FulfillmentsTable;
use App\Models\Fulfillment\Fulfillment;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use UnitEnum;

class FulfillmentResource extends Resource
{
    protected static ?string $model = Fulfillment::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-truck';

    protected static string|UnitEnum|null $navigationGroup = 'Customers & Sales';

    protected static ?string $navigationLabel = 'Fulfillments';

    protected static ?int $navigationSort = 9;

    public static function form(Schema $schema): Schema
    {
        return FulfillmentForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return FulfillmentsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            FulfillmentEventsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListFulfillments::route('/'),
        ];
    }
}
