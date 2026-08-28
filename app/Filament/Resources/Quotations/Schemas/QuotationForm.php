<?php

namespace App\Filament\Resources\Quotations\Schemas;

use App\Enums\Quotation\QuotationStatus;
use Filament\Forms\Components\Money;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
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
                            ->getOptionLabelUsing(fn ($record) => $record->reference . ' — ' . ($record->customer->user->name ?? 'Unknown'))
                            ->searchable()
                            ->preload()
                            ->required()
                            ->disabled(fn ($context, $record) => $context === 'edit' && $record !== null && !$record->canBeEdited()),

                        Select::make('status')
                            ->label('Status')
                            ->options(QuotationStatus::class)
                            ->required(),

                        Textarea::make('notes')
                            ->label('Notes')
                            ->rows(3)
                            ->columnSpanFull()
                            ->disabled(fn ($context, $record) => $context === 'edit' && $record !== null && !$record->canBeEdited()),
                    ]),

                Section::make('Commercial Terms')
                    ->columns(3)
                    ->columnSpanFull()
                    ->schema([
                        Money::make('subtotal')
                            ->label('Subtotal')
                            ->disabled()
                            ->dehydrated(false)
                            ->columnSpan(1),

                        Money::make('discount')
                            ->label('Discount')
                            ->default(0)
                            ->disabled(fn ($context, $record) => $context === 'edit' && $record !== null && !$record->canBeEdited())
                            ->columnSpan(1),

                        Money::make('total')
                            ->label('Total')
                            ->disabled()
                            ->dehydrated(false)
                            ->columnSpan(1),
                    ]),

                Section::make('Validity')
                    ->columns(2)
                    ->columnSpanFull()
                    ->schema([
                        Textarea::make('sent_at')
                            ->label('Sent At')
                            ->disabled()
                            ->dehydrated(false)
                            ->visible(fn ($record) => $record !== null && $record->isSent()),

                        Textarea::make('valid_until')
                            ->label('Valid Until')
                            ->disabled()
                            ->dehydrated(false)
                            ->visible(fn ($record) => $record !== null && $record->isSent()),
                    ]),

                Section::make('Line Items')
                    ->description('Add items to this quotation. Subtotal and total are calculated automatically.')
                    ->columnSpanFull()
                    ->schema([
                        Repeater::make('items')
                            ->label('Items')
                            ->relationship('items')
                            ->columns(4)
                            ->schema([
                                Textarea::make('name')
                                    ->label('Item')
                                    ->required()
                                    ->columnSpan(2),

                                Textarea::make('description')
                                    ->label('Description')
                                    ->columnSpan(2),

                                Money::make('unit_price')
                                    ->label('Unit Price')
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(fn ($state, callable $set, $context) => $context === 'edit' ? null : $set('subtotal', $state)),

                                TextInput::make('quantity')
                                    ->label('Quantity')
                                    ->numeric()
                                    ->default(1)
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(fn ($state, callable $set, $context) => $context === 'edit' ? null : $set('subtotal', $state)),

                                TextInput::make('unit')
                                    ->label('Unit')
                                    ->placeholder('piece, kg, litre...'),

                                Money::make('subtotal')
                                    ->label('Subtotal')
                                    ->disabled()
                                    ->dehydrated(false),
                            ])
                            ->reorderable(true)
                            ->defaultItems(1)
                            ->hiddenLabel()
                            ->columnSpanFull()
                            ->disabled(fn ($context, $record) => $context === 'edit' && $record !== null && !$record->canBeEdited()),
                    ]),
            ]);
    }
}
