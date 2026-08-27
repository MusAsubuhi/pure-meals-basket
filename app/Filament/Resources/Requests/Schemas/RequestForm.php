<?php

namespace App\Filament\Resources\Requests\Schemas;

use App\Enums\Request\RequestStatus;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;

class RequestForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Request Details')
                    ->columns(2)
                    ->columnSpanFull()
                    ->schema([
                        Forms\Components\Select::make('customer_id')
                            ->label('Customer')
                            ->relationship('customer', 'id')
                            ->getOptionLabelUsing(fn ($record) => $record->user->name ?? '')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->disabled(fn ($context) => $context === 'edit'),

                        Forms\Components\TextInput::make('reference')
                            ->label('Reference')
                            ->disabled()
                            ->default(fn () => \App\Models\Request\Request::generateReference()),

                        Forms\Components\Select::make('status')
                            ->label('Status')
                            ->options(RequestStatus::class)
                            ->required()
                            ->live(),

                        Forms\Components\DatePicker::make('event_date')
                            ->label('Event Date')
                            ->required(),

                        Forms\Components\TimePicker::make('event_time')
                            ->label('Event Time'),

                        Forms\Components\TextInput::make('location')
                            ->label('Location')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\Textarea::make('notes')
                            ->label('Notes')
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
