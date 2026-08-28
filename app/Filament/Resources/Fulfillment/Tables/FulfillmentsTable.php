<?php

namespace App\Filament\Resources\Fulfillment\Tables;

use App\Enums\Order\FulfillmentMethod;
use App\Enums\Order\FulfillmentStatus;
use App\Models\Fulfillment\Fulfillment;
use Filament\Tables;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class FulfillmentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('order.reference')
                    ->label('Order')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('method')
                    ->label('Method')
                    ->formatStateUsing(fn ($state) => $state->label())
                    ->badge()
                    ->color(fn ($state) => $state->badgeColor())
                    ->sortable(),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn ($state) => $state->badgeColor())
                    ->sortable(),

                TextColumn::make('scheduled_at')
                    ->label('Scheduled')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('delivery_address')
                    ->label('Delivery Address')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('recipient_name')
                    ->label('Recipient')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([
                Action::make('view')
                    ->label('View')
                    ->icon('heroicon-o-eye')
                    ->color('gray')
                    ->url(fn (Fulfillment $record) => route('filament.admin.resources.fulfillments.index')), // placeholder
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Fulfillment Status')
                    ->options(FulfillmentStatus::class),

                Tables\Filters\SelectFilter::make('method')
                    ->label('Fulfillment Method')
                    ->options(FulfillmentMethod::class),
            ]);
    }
}
