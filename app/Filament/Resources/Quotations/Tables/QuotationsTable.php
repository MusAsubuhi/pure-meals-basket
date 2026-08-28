<?php

namespace App\Filament\Resources\Quotations\Tables;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

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
            ->defaultSort('created_at', 'desc');
    }
}
