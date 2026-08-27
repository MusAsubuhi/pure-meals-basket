<?php

namespace App\Filament\Resources\Catalogue;

use App\Filament\Resources\Catalogue\Pages\ListProducts;
use App\Filament\Resources\Catalogue\Pages\CreateProduct;
use App\Filament\Resources\Catalogue\Pages\EditProduct;
use App\Filament\Resources\Catalogue\Relations\ProductOptionsRelationManager;
use App\Filament\Resources\Catalogue\Schemas\ProductForm;
use App\Filament\Resources\Catalogue\Tables\ProductsTable;
use App\Models\Product;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-cake';
    protected static string|UnitEnum|null $navigationGroup = 'Catalogue';
    protected static ?string $navigationLabel = 'Products';
    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return ProductForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ProductsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            ProductOptionsRelationManager::class,
        ];
    }

    // Catalogue items are archived, never destructively deleted
    public static function canDelete($record): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProducts::route('/'),
            'create' => CreateProduct::route('/create'),
            'edit' => EditProduct::route('/{record}/edit'),
        ];
    }
}
