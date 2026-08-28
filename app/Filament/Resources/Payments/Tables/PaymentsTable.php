<?php

namespace App\Filament\Resources\Payments\Tables;

use App\Enums\Payment\PaymentMethod;
use App\Enums\Payment\PaymentStatus;
use App\Models\Payment;
use App\Services\Payment\PaymentOrchestrator;
use Filament\Actions\Action;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Support\Facades\Auth;

class PaymentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('reference')
                    ->label('Reference')
                    ->searchable()
                    ->sortable(),

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

                TextColumn::make('amount')
                    ->label('Amount')
                    ->money('KES')
                    ->sortable(),

                TextColumn::make('paid_at')
                    ->label('Paid At')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([
                Action::make('confirmCash')
                    ->label('Confirm Cash')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (Payment $record) => $record->method === PaymentMethod::CASH && $record->isPending() && Auth::user()?->is_superadmin)
                    ->requiresConfirmation()
                    ->modalHeading('Confirm Cash Payment')
                    ->modalDescription(fn (Payment $record) => "Confirm cash payment {$record->reference} for KSh ".number_format($record->amount, 2).'?')
                    ->action(function (Payment $record) {
                        app(PaymentOrchestrator::class)->confirmCash($record, Auth::id());
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Payment Status')
                    ->options(PaymentStatus::class),

                Tables\Filters\SelectFilter::make('method')
                    ->label('Payment Method')
                    ->options(PaymentMethod::class),
            ]);
    }
}
