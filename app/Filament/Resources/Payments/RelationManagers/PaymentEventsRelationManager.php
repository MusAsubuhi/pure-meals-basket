<?php

namespace App\Filament\Resources\Payments\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PaymentEventsRelationManager extends RelationManager
{
    protected static string $relationship = 'events';

    protected static ?string $title = 'Events';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('event_type')
                    ->label('Event')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'SUCCESS' => 'success',
                        'FAILED' => 'danger',
                        'INITIATED' => 'gray',
                        'PROCESSING' => 'warning',
                        default => 'gray',
                    }),

                TextColumn::make('user.name')
                    ->label('User')
                    ->toggleable(),

                TextColumn::make('created_at')
                    ->label('When')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
