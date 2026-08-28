<?php

namespace App\Filament\Resources\Orders\Pages;

use App\Filament\Resources\Orders\OrderResource;
use App\Services\Order\OrderOrchestrator;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditOrder extends EditRecord
{
    protected static string $resource = OrderResource::class;

    public function __construct(
        protected OrderOrchestrator $orchestrator,
    ) {}

    protected function getHeaderActions(): array
    {
        return [
            Action::make('confirmPayment')
                ->label('Confirm Payment')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->visible(fn () => $this->record->canBeConfirmed() && $this->record->amount_paid >= $this->record->payment_required)
                ->requiresConfirmation()
                ->action(function () {
                    $this->orchestrator->confirmAfterPayment($this->record, auth()->id());
                    Notification::make()->success()->title('Order confirmed')->send();
                }),

            Action::make('startPreparing')
                ->label('Start Preparing')
                ->icon('heroicon-o-cog')
                ->color('primary')
                ->visible(fn () => $this->record->canStartPreparing())
                ->requiresConfirmation()
                ->action(function () {
                    $this->orchestrator->startPreparing($this->record, auth()->id());
                    Notification::make()->success()->title('Preparation started')->send();
                }),

            Action::make('markReady')
                ->label('Mark Ready')
                ->icon('heroicon-o-check')
                ->color('success')
                ->visible(fn () => $this->record->canMarkReady())
                ->requiresConfirmation()
                ->action(function () {
                    $this->orchestrator->markReady($this->record, auth()->id());
                    Notification::make()->success()->title('Order marked as ready')->send();
                }),

            Action::make('dispatch')
                ->label('Dispatch')
                ->icon('heroicon-o-truck')
                ->color('info')
                ->visible(fn () => $this->record->canDispatch())
                ->requiresConfirmation()
                ->action(function () {
                    $this->orchestrator->dispatch($this->record, auth()->id());
                    Notification::make()->success()->title('Order dispatched')->send();
                }),

            Action::make('markDelivered')
                ->label('Mark Delivered')
                ->icon('heroicon-o-home')
                ->color('success')
                ->visible(fn () => $this->record->canMarkDelivered())
                ->requiresConfirmation()
                ->action(function () {
                    $this->orchestrator->markDelivered($this->record, auth()->id());
                    Notification::make()->success()->title('Order delivered')->send();
                }),

            Action::make('complete')
                ->label('Complete')
                ->icon('heroicon-o-flag')
                ->color('success')
                ->visible(fn () => $this->record->canComplete())
                ->requiresConfirmation()
                ->action(function () {
                    $this->orchestrator->complete($this->record, auth()->id());
                    Notification::make()->success()->title('Order completed')->send();
                }),

            Action::make('cancel')
                ->label('Cancel')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->visible(fn () => $this->record->canBeCancelled())
                ->requiresConfirmation()
                ->action(function () {
                    $this->orchestrator->cancel($this->record, auth()->id());
                    Notification::make()->success()->title('Order cancelled')->send();
                }),

            Actions\DeleteAction::make()
                ->visible(fn () => $this->record->canBeCancelled()),
        ];
    }
}
