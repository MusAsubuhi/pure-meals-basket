<?php

namespace App\Filament\Resources\Requests\Pages;

use App\Enums\Request\RequestStatus;
use App\Filament\Resources\Requests\RequestResource;
use App\Services\Request\RequestOrchestrator;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditRequest extends EditRecord
{
    protected static string $resource = RequestResource::class;

    public function __construct(
        protected RequestOrchestrator $orchestrator,
    ) {}

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('startReview')
                ->label('Start Review')
                ->icon('heroicon-o-eye')
                ->color('warning')
                ->visible(fn () => $this->record->status === RequestStatus::SUBMITTED)
                ->requiresConfirmation()
                ->action(function () {
                    $this->orchestrator->startReview($this->record, auth()->id());
                    Notification::make()->success()->title('Review started')->send();
                }),

            Actions\Action::make('requestInformation')
                ->label('Request Information')
                ->icon('heroicon-o-question-mark-circle')
                ->color('orange')
                ->visible(fn () => $this->record->status === RequestStatus::UNDER_REVIEW)
                ->form([
                    Forms\Components\Textarea::make('question')
                        ->label('Question')
                        ->required()
                        ->rows(3),
                ])
                ->action(function (array $data) {
                    $this->orchestrator->requestInformation($this->record, auth()->id(), $data['question']);
                    Notification::make()->success()->title('Information requested')->send();
                }),

            Actions\Action::make('markReadyForCheckout')
                ->label('Ready for Checkout')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->visible(fn () => $this->record->status === RequestStatus::UNDER_REVIEW)
                ->requiresConfirmation()
                ->action(function () {
                    $this->orchestrator->markReadyForCheckout($this->record, auth()->id());
                    Notification::make()->success()->title('Request approved')->send();
                }),

            Actions\Action::make('decline')
                ->label('Decline')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->visible(fn () => in_array($this->record->status, [
                    RequestStatus::SUBMITTED,
                    RequestStatus::UNDER_REVIEW,
                    RequestStatus::NEEDS_INFORMATION,
                ], true))
                ->form([
                    Forms\Components\Textarea::make('reason')
                        ->label('Reason')
                        ->required()
                        ->rows(3),
                ])
                ->action(function (array $data) {
                    $this->orchestrator->decline($this->record, auth()->id(), $data['reason']);
                    Notification::make()->success()->title('Request declined')->send();
                }),

            Actions\DeleteAction::make()
                ->visible(fn () => $this->record->status === RequestStatus::DRAFT),
        ];
    }
}
