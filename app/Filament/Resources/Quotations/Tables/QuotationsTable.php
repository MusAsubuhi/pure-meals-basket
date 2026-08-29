<?php

namespace App\Filament\Resources\Quotations\Tables;

use App\Enums\Quotation\QuotationStatus;
use App\Services\Order\OrderOrchestrator;
use App\Services\Quotation\QuotationOrchestrator;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class QuotationsTable
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

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn ($state) => $state->badgeColor())
                    ->sortable(),

                TextColumn::make('total')
                    ->label('Total')
                    ->money('KES')
                    ->sortable(),

                TextColumn::make('valid_until')
                    ->label('Valid Until')
                    ->date()
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([
                ActionGroup::make([
                    EditAction::make(),
                    self::sendAction(),
                    self::acceptAction(),
                    self::declineAction(),
                    self::withdrawAndReplaceAction(),
                    self::expireAction(),
                    self::createOrderAction(),
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

    protected static function sendAction(): Action
    {
        return Action::make('send')
            ->label('Send to customer')
            ->icon('heroicon-o-paper-airplane')
            ->color('info')
            ->requiresConfirmation()
            ->modalHeading('Send quotation')
            ->modalDescription('This sends the quotation to the customer, valid for 7 days. Any other sent quotations for the same request will be withdrawn.')
            ->visible(fn ($record) => $record->status === QuotationStatus::DRAFT)
            ->action(function ($record) {
                self::runAction(
                    fn () => app(QuotationOrchestrator::class)->send($record, Auth::id()),
                    'Quotation sent to customer.',
                );
            });
    }

    protected static function acceptAction(): Action
    {
        return Action::make('accept')
            ->label('Accept')
            ->icon('heroicon-o-check-circle')
            ->color('success')
            ->requiresConfirmation()
            ->modalHeading('Accept quotation')
            ->modalDescription('Marks the quotation as accepted and moves its request to Ready for Checkout.')
            ->visible(fn ($record) => $record->status === QuotationStatus::SENT)
            ->action(function ($record) {
                self::runAction(
                    fn () => app(QuotationOrchestrator::class)->accept($record, Auth::id()),
                    'Quotation accepted.',
                );
            });
    }

    protected static function declineAction(): Action
    {
        return Action::make('decline')
            ->label('Decline')
            ->icon('heroicon-o-x-circle')
            ->color('danger')
            ->requiresConfirmation()
            ->modalDescription('Marks the quotation as declined by the customer.')
            ->visible(fn ($record) => $record->status === QuotationStatus::SENT)
            ->form([
                Textarea::make('reason')
                    ->label('Reason (for reference)')
                    ->required()
                    ->maxLength(2000),
            ])
            ->action(function ($record, array $data) {
                self::runAction(
                    function () use ($record, $data) {
                        app(QuotationOrchestrator::class)->decline($record, Auth::id());
                        $record->logEvent('DECLINE_NOTED', 'Declined via admin panel: '.($data['reason'] ?? 'no reason given'), Auth::id());
                    },
                    'Quotation declined.',
                );
            });
    }

    protected static function withdrawAndReplaceAction(): Action
    {
        return Action::make('withdrawAndReplace')
            ->label('Withdraw & replace')
            ->icon('heroicon-o-arrow-path')
            ->color('warning')
            ->requiresConfirmation()
            ->modalHeading('Withdraw and create replacement')
            ->modalDescription('The current quotation is withdrawn and a new draft quotation is created with the same items and discount for you to revise.')
            ->visible(fn ($record) => $record->status === QuotationStatus::SENT)
            ->action(function ($record) {
                self::runAction(
                    fn () => app(QuotationOrchestrator::class)->createReplacement($record, Auth::id()),
                    'Replacement quotation created.',
                );
            });
    }

    protected static function expireAction(): Action
    {
        return Action::make('expire')
            ->label('Mark expired')
            ->icon('heroicon-o-clock')
            ->color('gray')
            ->requiresConfirmation()
            ->visible(fn ($record) => $record->status === QuotationStatus::SENT && $record->hasExpired())
            ->action(function ($record) {
                self::runAction(
                    fn () => app(QuotationOrchestrator::class)->expire($record, Auth::id()),
                    'Quotation marked as expired.',
                );
            });
    }

    protected static function createOrderAction(): Action
    {
        return Action::make('createOrder')
            ->label('Create order')
            ->icon('heroicon-o-shopping-cart')
            ->color('primary')
            ->requiresConfirmation()
            ->modalHeading('Create order from quotation')
            ->modalDescription('An order will be created with all quotation items, pending payment.')
            ->visible(
                fn ($record) => $record->status === QuotationStatus::ACCEPTED
                    && ! $record->order()->exists(),
            )
            ->action(function ($record) {
                self::runAction(
                    fn () => app(OrderOrchestrator::class)->createFromQuotation($record, Auth::id()),
                    'Order created from quotation.',
                );
            });
    }
}
