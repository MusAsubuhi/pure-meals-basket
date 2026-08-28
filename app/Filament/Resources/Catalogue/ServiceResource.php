<?php

namespace App\Filament\Resources\Catalogue;

use App\Filament\Resources\Catalogue\Pages\CreateService;
use App\Filament\Resources\Catalogue\Pages\EditService;
use App\Filament\Resources\Catalogue\Pages\ListServices;
use App\Filament\Resources\Catalogue\Schemas\ServiceForm;
use App\Filament\Resources\Catalogue\Tables\ServicesTable;
use App\Models\Service;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use UnitEnum;

class ServiceResource extends Resource
{
    protected static ?string $model = Service::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-briefcase';

    protected static string|UnitEnum|null $navigationGroup = 'Catalogue';

    protected static ?string $navigationLabel = 'Services';

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return ServiceForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ServicesTable::configure($table);
    }

    // Catalogue items are archived, never destructively deleted
    public static function canDelete($record): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListServices::route('/'),
            'create' => CreateService::route('/create'),
            'edit' => EditService::route('/{record}/edit'),
        ];
    }
}
