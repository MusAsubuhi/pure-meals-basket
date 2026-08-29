<?php

namespace App\Filament\Resources\Quotations\Pages;

use App\Filament\Resources\Quotations\QuotationResource;
use App\Services\Quotation\QuotationOrchestrator;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditQuotation extends EditRecord
{
    protected static string $resource = QuotationResource::class;

    protected function orchestrator(): QuotationOrchestrator
    {
        return app(QuotationOrchestrator::class);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('send')
                ->label('Send Quotation')
                ->icon('heroicon-o-paper-airplane')
                ->color('success')
                ->visible(fn () => $this->record->canBeSent())
                ->requiresConfirmation()
                ->action(function () {
                    $this->orchestrator()->send($this->record, auth()->id());
                    Notification::make()->success()->title('Quotation sent')->send();
                }),

            Action::make('withdraw')
                ->label('Withdraw')
                ->icon('heroicon-o-arrow-uturn-left')
                ->color('warning')
                ->visible(fn () => $this->record->canBeWithdrawn())
                ->requiresConfirmation()
                ->action(function () {
                    $this->orchestrator()->withdraw($this->record, auth()->id());
                    Notification::make()->success()->title('Quotation withdrawn')->send();
                }),

            Action::make('accept')
                ->label('Accept')
                ->icon('heroicon-o-check')
                ->color('success')
                ->visible(fn () => $this->record->canBeAccepted())
                ->requiresConfirmation()
                ->action(function () {
                    $this->orchestrator()->accept($this->record, auth()->id());
                    Notification::make()->success()->title('Quotation accepted')->send();
                }),

            Action::make('decline')
                ->label('Decline')
                ->icon('heroicon-o-x-mark')
                ->color('danger')
                ->visible(fn () => $this->record->canBeDeclined())
                ->requiresConfirmation()
                ->action(function () {
                    $this->orchestrator()->decline($this->record, auth()->id());
                    Notification::make()->success()->title('Quotation declined')->send();
                }),

            Action::make('createReplacement')
                ->label('Create Replacement')
                ->icon('heroicon-o-arrow-path')
                ->color('gray')
                ->visible(fn () => $this->record->canBeReplaced())
                ->action(function () {
                    $replacement = $this->orchestrator()->createReplacement($this->record, auth()->id());
                    Notification::make()->success()->title('Replacement created')->send();
                    $this->redirect(static::getResource()::getUrl('edit', ['record' => $replacement]));
                }),

            Actions\DeleteAction::make()
                ->visible(fn () => $this->record->canBeEdited()),
        ];
    }
}
