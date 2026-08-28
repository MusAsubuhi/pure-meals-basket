<?php

namespace App\Filament\Resources\Fulfillment\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;

class FulfillmentEventsRelationManager extends RelationManager
{
    protected static string $recordLabel = 'Event';

    protected static string $relationship = 'events';

    public function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->columns([
                TextColumn::make('event_type')
                    ->label('Event')
                    ->badge()
                    ->color('gray'),

                TextColumn::make('description')
                    ->label('Description')
                    ->limit(50),

                TextColumn::make('user.name')
                    ->label('User')
                    ->placeholder('System'),

                TextColumn::make('created_at')
                    ->label('When')
                    ->dateTime(),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
