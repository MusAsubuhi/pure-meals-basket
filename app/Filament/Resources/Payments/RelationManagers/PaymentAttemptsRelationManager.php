<?php

namespace App\Filament\Resources\Payments\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PaymentAttemptsRelationManager extends RelationManager
{
    protected static string $relationship = 'attempts';

    protected static ?string $title = 'Attempts';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('attempt_reference')
                    ->label('Reference')
                    ->searchable(),

                TextColumn::make('provider')
                    ->label('Provider')
                    ->badge(),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge(),

                TextColumn::make('checkout_request_id')
                    ->label('Checkout ID')
                    ->toggleable(),

                TextColumn::make('initiated_at')
                    ->label('Initiated')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('completed_at')
                    ->label('Completed')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
