<?php

namespace App\Filament\Resources\Orders\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class OrderItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';

    protected static ?string $title = 'Items';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                // Read-only snapshots
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Item')
                    ->searchable(),

                TextColumn::make('description')
                    ->label('Description')
                    ->toggleable(),

                TextColumn::make('quantity')
                    ->label('Quantity')
                    ->suffix(' '.fn ($record) => $record->unit ?? ''),

                TextColumn::make('unit_price')
                    ->label('Unit Price')
                    ->money('KES')
                    ->toggleable(),

                TextColumn::make('subtotal')
                    ->label('Subtotal')
                    ->money('KES')
                    ->summarize([
                        Tables\Columns\Summarizers\Sum::make('money', 'KES'),
                    ]),
            ])
            ->defaultSort('id')
            ->headerActions([
                // No create - items are snapshots
            ])
            ->recordActions([
                // No edit - items are snapshots
            ]);
    }
}
