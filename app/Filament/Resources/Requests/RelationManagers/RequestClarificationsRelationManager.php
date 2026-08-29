<?php

namespace App\Filament\Resources\Requests\RelationManagers;

use App\Models\Request\RequestClarification;
use Filament\Actions\EditAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class RequestClarificationsRelationManager extends RelationManager
{
    protected static string $relationship = 'clarifications';

    protected static ?string $title = 'Clarifications';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Forms\Components\Textarea::make('question')
                    ->label('Question')
                    ->required()
                    ->rows(3)
                    ->disabled(fn ($context) => $context === 'edit'),

                Forms\Components\Textarea::make('response')
                    ->label('Response')
                    ->rows(3)
                    ->visible(fn ($context) => $context === 'edit'),

                Forms\Components\DateTimePicker::make('responded_at')
                    ->label('Responded At')
                    ->disabled()
                    ->visible(fn ($context) => $context === 'edit'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('question')
                    ->label('Question')
                    ->wrap()
                    ->limit(100),

                Tables\Columns\IconColumn::make('hasBeenAnswered')
                    ->label('Answered')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle'),

                Tables\Columns\TextColumn::make('askedBy.name')
                    ->label('Asked By')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('respondedBy.name')
                    ->label('Responded By')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('responded_at')
                    ->label('Responded At')
                    ->dateTime()
                    ->toggleable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([
                EditAction::make()
                    ->label('Respond')
                    ->visible(fn ($record) => ! $record->hasBeenAnswered()),
            ]);
    }
}
