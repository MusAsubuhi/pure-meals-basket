<?php

namespace App\Filament\Resources\Orders\Tables;

use App\Enums\Order\FulfillmentMethod;
use App\Enums\Order\OrderStatus;
use App\Enums\Order\PaymentStatus;
use App\Services\Order\OrderOrchestrator;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

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
            ])
            ->recordActions([
                ActionGroup::make([
                    self::recordPaymentAction(),
                    self::confirmAction(),
                    self::setFulfillmentAction(),
                    self::startPreparingAction(),
                    self::markReadyAction(),
                    self::dispatchAction(),
                    self::markDeliveredAction(),
                    self::completeAction(),
                    self::cancelAction(),
                ])->iconButton(),
            ]);
    }

    protected static function runAction(callable $callback, string $success): void
    {
        try {
            $callback();

            Notification::make()->title($success)->success()->send();
        } catch (\Throwable $e) {
            Notification::make()
                ->title('Action failed')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    protected static function recordPaymentAction(): Action
    {
        return Action::make('recordPayment')
            ->label('Record payment')
            ->icon('heroicon-o-currency-dollar')
            ->color('success')
            ->visible(fn ($record) => ! $record->isTerminal() && $record->balance_due > 0)
            ->form([
                Forms\Components\TextInput::make('amount')
                    ->label('Amount received (KSh)')
                    ->numeric()
                    ->required()
                    ->minValue(0.01)
                    ->default(fn ($record) => (float) $record->balance_due),
            ])
            ->action(function ($record, array $data) {
                self::runAction(
                    fn () => app(OrderOrchestrator::class)->recordPayment($record, (float) $data['amount'], Auth::id()),
                    'Payment recorded.',
                );
            });
    }

    protected static function confirmAction(): Action
    {
        return Action::make('confirm')
            ->label('Confirm order')
            ->icon('heroicon-o-check-circle')
            ->color('success')
            ->requiresConfirmation()
            ->modalDescription('Confirms the order. Requires that the mandatory payment (70%) has been received.')
            ->visible(fn ($record) => $record->status === OrderStatus::PENDING_PAYMENT)
            ->action(function ($record) {
                self::runAction(
                    fn () => app(OrderOrchestrator::class)->confirmAfterPayment($record, Auth::id()),
                    'Order confirmed.',
                );
            });
    }

    protected static function setFulfillmentAction(): Action
    {
        return Action::make('setFulfillment')
            ->label('Set fulfillment')
            ->icon('heroicon-o-truck')
            ->color('info')
            ->visible(
                fn ($record) => $record->status === OrderStatus::CONFIRMED
                    && ! $record->fulfillment()->exists(),
            )
            ->form([
                Forms\Components\Select::make('method')
                    ->label('Fulfillment method')
                    ->options(FulfillmentMethod::class)
                    ->required(),
                Forms\Components\TextInput::make('delivery_fee')
                    ->label('Delivery fee (KSh)')
                    ->numeric()
                    ->default(0)
                    ->minValue(0),
            ])
            ->action(function ($record, array $data) {
                self::runAction(
                    fn () => app(OrderOrchestrator::class)->setFulfillment(
                        $record,
                        $data['method'],
                        (float) ($data['delivery_fee'] ?? 0),
                        Auth::id(),
                    ),
                    'Fulfillment method set.',
                );
            });
    }

    protected static function startPreparingAction(): Action
    {
        return Action::make('startPreparing')
            ->label('Start preparing')
            ->icon('heroicon-o-fire')
            ->color('warning')
            ->visible(fn ($record) => $record->status === OrderStatus::CONFIRMED)
            ->action(function ($record) {
                self::runAction(
                    fn () => app(OrderOrchestrator::class)->startPreparing($record, Auth::id()),
                    'Order preparation started.',
                );
            });
    }

    protected static function markReadyAction(): Action
    {
        return Action::make('markReady')
            ->label('Mark ready')
            ->icon('heroicon-o-bell-alert')
            ->color('success')
            ->visible(fn ($record) => $record->status === OrderStatus::PREPARING)
            ->action(function ($record) {
                self::runAction(
                    fn () => app(OrderOrchestrator::class)->markReady($record, Auth::id()),
                    'Order marked as ready.',
                );
            });
    }

    protected static function dispatchAction(): Action
    {
        return Action::make('dispatch')
            ->label('Dispatch for delivery')
            ->icon('heroicon-o-map-pin')
            ->color('info')
            ->requiresConfirmation()
            ->visible(
                fn ($record) => $record->status === OrderStatus::READY
                    && $record->fulfillment_method === FulfillmentMethod::DELIVERY,
            )
            ->action(function ($record) {
                self::runAction(
                    fn () => app(OrderOrchestrator::class)->dispatch($record, Auth::id()),
                    'Order dispatched for delivery.',
                );
            });
    }

    protected static function markDeliveredAction(): Action
    {
        return Action::make('markDelivered')
            ->label('Mark delivered')
            ->icon('heroicon-o-home')
            ->color('success')
            ->visible(fn ($record) => $record->status === OrderStatus::OUT_FOR_DELIVERY)
            ->action(function ($record) {
                self::runAction(
                    fn () => app(OrderOrchestrator::class)->markDelivered($record, Auth::id()),
                    'Order marked as delivered.',
                );
            });
    }

    protected static function completeAction(): Action
    {
        return Action::make('complete')
            ->label('Complete order')
            ->icon('heroicon-o-flag')
            ->color('success')
            ->requiresConfirmation()
            ->modalDescription('Completes the order and its fulfillment. This is a terminal state.')
            ->visible(fn ($record) => $record->status->canComplete())
            ->action(function ($record) {
                self::runAction(
                    fn () => app(OrderOrchestrator::class)->complete($record, Auth::id()),
                    'Order completed.',
                );
            });
    }

    protected static function cancelAction(): Action
    {
        return Action::make('cancelOrder')
            ->label('Cancel order')
            ->icon('heroicon-o-x-circle')
            ->color('danger')
            ->requiresConfirmation()
            ->modalDescription('This cancels the order and cannot be undone.')
            ->visible(fn ($record) => $record->status->canBeCancelled())
            ->action(function ($record) {
                self::runAction(
                    fn () => app(OrderOrchestrator::class)->cancel($record, Auth::id()),
                    'Order cancelled.',
                );
            });
    }

}
