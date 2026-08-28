<?php

namespace App\Filament\Resources\Orders\Tables;

use App\Enums\Order\OrderStatus;
use App\Enums\Order\PaymentStatus;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;

class OrdersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('reference')
                    ->label('Reference')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('request.reference')
                    ->label('Request')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('customer_name')
                    ->label('Customer')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn ($state) => $state->badgeColor())
                    ->sortable(),

                TextColumn::make('payment_status')
                    ->label('Payment')
                    ->badge()
                    ->color(fn ($state) => $state->badgeColor())
                    ->sortable(),

                TextColumn::make('fulfillment_method')
                    ->label('Fulfillment')
                    ->formatStateUsing(fn ($state) => $state?->label() ?? 'N/A')
                    ->badge()
                    ->color(fn ($state) => $state?->badgeColor() ?? 'gray')
                    ->toggleable(),

                TextColumn::make('total')
                    ->label('Total')
                    ->money('KES')
                    ->sortable(),

                TextColumn::make('balance_due')
                    ->label('Balance')
                    ->money('KES')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('event_date')
                    ->label('Event Date')
                    ->date()
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Order Status')
                    ->options(OrderStatus::class),

                Tables\Filters\SelectFilter::make('payment_status')
                    ->label('Payment Status')
                    ->options(PaymentStatus::class),
            ]);
    }
}
