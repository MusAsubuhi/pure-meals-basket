<?php

namespace App\Filament\Resources\Requests;

use App\Filament\Resources\Requests\Pages\CreateRequest;
use App\Filament\Resources\Requests\Pages\EditRequest;
use App\Filament\Resources\Requests\Pages\ListRequests;
use App\Filament\Resources\Requests\RelationManagers\RequestClarificationsRelationManager;
use App\Filament\Resources\Requests\RelationManagers\RequestEventsRelationManager;
use App\Filament\Resources\Requests\RelationManagers\RequestItemsRelationManager;
use App\Filament\Resources\Requests\Schemas\RequestForm;
use App\Filament\Resources\Requests\Tables\RequestsTable;
use App\Models\Request\Request as RequestModel;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use UnitEnum;

class RequestResource extends Resource
{
    protected static ?string $model = RequestModel::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static string|UnitEnum|null $navigationGroup = 'Customers & Sales';

    protected static ?string $navigationLabel = 'Requests';

    protected static ?int $navigationSort = 5;

    public static function form(Schema $schema): Schema
    {
        return RequestForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return RequestsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            RequestItemsRelationManager::class,
            RequestEventsRelationManager::class,
            RequestClarificationsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRequests::route('/'),
            'create' => CreateRequest::route('/create'),
            'edit' => EditRequest::route('/{record}/edit'),
        ];
    }
}
