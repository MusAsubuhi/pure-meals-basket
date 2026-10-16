<?php

namespace App\Filament\Resources\Delivery\Tables;

use App\Models\DeliveryZone;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class DeliveryZonesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Zone')
                    ->searchable()
                    ->sortable()
                    ->weight('medium')
                    ->icon('heroicon-o-map')
                    ->description(fn (DeliveryZone $record): string => $record->slug)
                    ->wrap(),

                TextColumn::make('delivery_charge')
                    ->label('Delivery charge')
                    ->formatStateUsing(fn ($state) => 'KSh '.number_format((float) $state, 2))
                    ->sortable()
                    ->alignRight(),

                TextColumn::make('free_delivery_above')
                    ->label('Free above')
                    ->formatStateUsing(fn ($state) => $state === null ? '—' : 'KSh '.number_format((float) $state, 2))
                    ->sortable()
                    ->alignRight(),

                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->sortable(),

                TextColumn::make('orders_count')
                    ->label('Orders')
                    ->counts('orders')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TernaryFilter::make('is_active')
                    ->label('Active')
                    ->placeholder('All zones')
                    ->trueLabel('Active only')
                    ->falseLabel('Inactive only'),
            ])
            ->defaultSort('sort_order')
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}