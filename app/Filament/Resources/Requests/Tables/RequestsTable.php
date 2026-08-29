<?php

namespace App\Filament\Resources\Requests\Tables;

use App\Enums\Request\RequestStatus;
use App\Services\Request\RequestOrchestrator;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class RequestsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('reference')
                    ->label('Reference')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('customer.user.name')
                    ->label('Customer')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn ($state) => $state->badgeColor()),

                Tables\Columns\TextColumn::make('event_date')
                    ->label('Event Date')
                    ->date()
                    ->sortable(),

                Tables\Columns\TextColumn::make('location')
                    ->label('Location')
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('submitted_at')
                    ->label('Submitted')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(RequestStatus::class),
            ])
            ->recordActions([
                ActionGroup::make([
                    EditAction::make(),
                    self::startReviewAction(),
                    self::requestInformationAction(),
                    self::createQuotationAction(),
                    self::declineAction(),
                    self::cancelAction(),
                ])->iconButton(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
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

    protected static function startReviewAction(): Action
    {
        return Action::make('startReview')
            ->label('Start review')
            ->icon('heroicon-o-magnifying-glass')
            ->color('info')
            ->visible(fn ($record) => $record->status === RequestStatus::SUBMITTED)
            ->action(function ($record) {
                self::runAction(
                    fn () => app(RequestOrchestrator::class)->startReview($record, (int) Auth::id()),
                    'Request review started.',
                );
            });
    }

    protected static function requestInformationAction(): Action
    {
        return Action::make('requestInformation')
            ->label('Request information')
            ->icon('heroicon-o-chat-bubble-bottom-center-text')
            ->color('warning')
            ->visible(fn ($record) => $record->status === RequestStatus::UNDER_REVIEW)
            ->form([
                Textarea::make('question')
                    ->label('Question for the customer')
                    ->required()
                    ->maxLength(2000),
            ])
            ->action(function ($record, array $data) {
                self::runAction(
                    fn () => app(RequestOrchestrator::class)->requestInformation($record, (int) Auth::id(), $data['question']),
                    'Information requested from the customer.',
                );
            });
    }

    protected static function createQuotationAction(): Action
    {
        return Action::make('createQuotation')
            ->label('Create quotation')
            ->icon('heroicon-o-document-text')
            ->color('primary')
            ->requiresConfirmation()
            ->modalHeading('Create quotation from request')
            ->modalDescription('A draft quotation will be created with all request items pre-filled, and the request will move to Quotation Required.')
            ->visible(
                fn ($record) => in_array($record->status, [
                    RequestStatus::SUBMITTED,
                    RequestStatus::UNDER_REVIEW,
                    RequestStatus::QUOTATION_REQUIRED,
                ], true),
            )
            ->action(function ($record) {
                try {
                    $quotation = app(RequestOrchestrator::class)->createQuotationFromRequest($record, (int) Auth::id());

                    Notification::make()
                        ->title('Quotation created.')
                        ->success()
                        ->send();

                    return redirect(\App\Filament\Resources\Quotations\QuotationResource::getUrl('edit', ['record' => $quotation]));
                } catch (\Throwable $e) {
                    Notification::make()
                        ->title('Action failed')
                        ->body($e->getMessage())
                        ->danger()
                        ->send();
                }
            });
    }

    protected static function declineAction(): Action
    {
        return Action::make('decline')
            ->label('Decline request')
            ->icon('heroicon-o-x-circle')
            ->color('danger')
            ->requiresConfirmation()
            ->visible(fn ($record) => ! in_array($record->status, [RequestStatus::DECLINED, RequestStatus::CANCELLED], true))
            ->form([
                Textarea::make('reason')
                    ->label('Reason')
                    ->required()
                    ->maxLength(2000),
            ])
            ->action(function ($record, array $data) {
                self::runAction(
                    fn () => app(RequestOrchestrator::class)->decline($record, (int) Auth::id(), $data['reason']),
                    'Request declined.',
                );
            });
    }

    protected static function cancelAction(): Action
    {
        return Action::make('cancelRequest')
            ->label('Cancel request')
            ->icon('heroicon-o-archive-box-x-mark')
            ->color('gray')
            ->requiresConfirmation()
            ->modalDescription('This cancels the request and cannot be undone.')
            ->visible(fn ($record) => in_array($record->status, [RequestStatus::DRAFT, RequestStatus::SUBMITTED], true))
            ->form([
                Textarea::make('reason')
                    ->label('Reason')
                    ->required()
                    ->maxLength(2000),
            ])
            ->action(function ($record, array $data) {
                self::runAction(
                    fn () => app(RequestOrchestrator::class)->cancel($record, (int) Auth::id(), $data['reason']),
                    'Request cancelled.',
                );
            });
    }
}
