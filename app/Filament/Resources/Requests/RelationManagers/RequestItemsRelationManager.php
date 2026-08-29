<?php

namespace App\Filament\Resources\Requests\RelationManagers;

use App\Enums\Request\RequestItemPricingStatus;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class RequestItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';

    protected static ?string $title = 'Items';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                // Read-only - items are snapshots from catalogue
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Item')
                    ->searchable(),

                Tables\Columns\TextColumn::make('item_type')
                    ->label('Type')
                    ->badge()
                    ->color(fn ($state) => $state === 'product' ? 'primary' : 'success'),

                Tables\Columns\TextColumn::make('quantity')
                    ->label('Quantity')
                    ->formatStateUsing(fn ($state, $record) => $state . ' ' . ($record->unit ?? '')),

                Tables\Columns\TextColumn::make('pricing_type')
                    ->label('Pricing Type')
                    ->formatStateUsing(fn ($state) => $state?->label() ?? 'N/A'),

                Tables\Columns\TextColumn::make('pricing_status')
                    ->label('Status')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        RequestItemPricingStatus::CALCULATED => 'success',
                        RequestItemPricingStatus::QUOTATION_REQUIRED => 'warning',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('unit_price')
                    ->label('Unit Price')
                    ->money('KES')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('subtotal')
                    ->label('Subtotal')
                    ->money('KES')
                    ->summarize([
                        Tables\Columns\Summarizers\Sum::make('money', 'KES'),
                    ]),
            ])
            ->defaultSort('id')
            ->headerActions([
                // No create - items come from cart
            ])
            ->recordActions([
                // No edit - items are snapshots
            ]);
    }
}
