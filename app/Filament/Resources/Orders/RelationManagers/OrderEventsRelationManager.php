<?php

namespace App\Filament\Resources\Orders\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class OrderEventsRelationManager extends RelationManager
{
    protected static string $relationship = 'events';

    protected static ?string $title = 'Events';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                // Read-only audit trail
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('event_type')
                    ->label('Event')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'CREATED' => 'gray',
                        'PAYMENT_RECEIVED' => 'success',
                        'CONFIRMED' => 'info',
                        'PREPARING' => 'primary',
                        'READY' => 'success',
                        'OUT_FOR_DELIVERY' => 'info',
                        'DELIVERED' => 'success',
                        'COMPLETED' => 'success',
                        'CANCELLED' => 'danger',
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
            ->defaultSort('created_at', 'desc')
            ->headerActions([
                // No create - events are append-only
            ])
            ->recordActions([
                // No edit - events are append-only
            ]);
    }
}
