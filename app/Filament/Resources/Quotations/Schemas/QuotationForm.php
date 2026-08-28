<?php

namespace App\Filament\Resources\Quotations\Schemas;

use App\Enums\Quotation\QuotationStatus;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class QuotationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Quotation Details')
                    ->columns(2)
                    ->columnSpanFull()
                    ->schema([
                        Select::make('request_id')
                            ->label('Request')
                            ->relationship('request', 'reference')
                            ->getOptionLabelUsing(fn ($record) => $record->reference.' — '.($record->customer->user->name ?? 'Unknown'))
                            ->searchable()
                            ->preload()
                            ->required()
                            ->disabled(fn ($context, $record) => $context === 'edit' && $record !== null && ! $record->canBeEdited()),

                        Select::make('status')
                            ->label('Status')
                            ->options(QuotationStatus::class)
                            ->required(),

                        Textarea::make('notes')
                            ->label('Notes')
                            ->rows(3)
                            ->columnSpanFull()
                            ->disabled(fn ($context, $record) => $context === 'edit' && $record !== null && ! $record->canBeEdited()),
                    ]),

                Section::make('Commercial Terms')
                    ->columns(3)
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('subtotal')
                            ->label('Subtotal')
                            ->numeric()
                            ->prefix('KSh')
                            ->disabled()
                            ->dehydrated(false)
                            ->columnSpan(1),

                        TextInput::make('discount')
                            ->label('Discount')
                            ->numeric()
                            ->prefix('KSh')
                            ->default(0)
                            ->disabled(fn ($context, $record) => $context === 'edit' && $record !== null && ! $record->canBeEdited())
                            ->columnSpan(1),

                        TextInput::make('total')
                            ->label('Total')
                            ->numeric()
                            ->prefix('KSh')
                            ->disabled()
                            ->dehydrated(false)
                            ->columnSpan(1),
                    ]),

                Section::make('Validity')
                    ->columns(2)
                    ->columnSpanFull()
                    ->visible(fn ($record) => $record !== null && $record->isSent())
                    ->schema([
                        TextInput::make('sent_at')
                            ->label('Sent At')
                            ->disabled()
                            ->dehydrated(false),

                        TextInput::make('valid_until')
                            ->label('Valid Until')
                            ->disabled()
                            ->dehydrated(false),
                    ]),

                Section::make('Line Items')
                    ->description('Add items to this quotation. Subtotal is calculated automatically.')
                    ->columnSpanFull()
                    ->schema([
                        Repeater::make('items')
                            ->label('Items')
                            ->relationship('items')
                            ->columns(4)
                            ->schema([
                                TextInput::make('name')
                                    ->label('Item')
                                    ->required()
                                    ->columnSpan(2),

                                Textarea::make('description')
                                    ->label('Description')
                                    ->columnSpan(2),

                                TextInput::make('unit_price')
                                    ->label('Unit Price')
                                    ->numeric()
                                    ->prefix('KSh')
                                    ->required()
                                    ->live()
                                    ->columnSpan(1),

                                TextInput::make('quantity')
                                    ->label('Quantity')
                                    ->numeric()
                                    ->default(1)
                                    ->required()
                                    ->live()
                                    ->columnSpan(1),

                                TextInput::make('unit')
                                    ->label('Unit')
                                    ->placeholder('piece, kg, litre...')
                                    ->columnSpan(1),

                                TextInput::make('subtotal')
                                    ->label('Subtotal')
                                    ->numeric()
                                    ->prefix('KSh')
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->columnSpan(1),
                            ])
                            ->reorderable(true)
                            ->defaultItems(1)
                            ->hiddenLabel()
                            ->columnSpanFull()
                            ->disabled(fn ($context, $record) => $context === 'edit' && $record !== null && ! $record->canBeEdited()),
                    ]),
            ]);
    }
}
