<?php

namespace App\Filament\Resources\Catalogue\Schemas;

use App\Enums\PricingType;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;

/**
 * Shared dynamic pricing configuration used by both ProductForm and
 * ServiceForm. Mirrors the spec's admin UX: a single friendly question —
 * "How is this item priced?" — with fields that appear per rule.
 */
class PricingFields
{
    public static function make(): array
    {
        return [
            Section::make('How is this priced?')
                ->description('The pricing engine uses this rule to calculate every customer price.')
                ->columnSpanFull()
                ->schema([
                    Select::make('pricing_type')
                        ->label('Pricing method')
                        ->options(PricingType::options())
                        ->default(PricingType::FIXED->value)
                        ->required()
                        ->live(),

                    // Fixed price
                    Section::make('Fixed Price')
                        ->visible(fn ($get) => $get('pricing_type') === PricingType::FIXED->value)
                        ->columns(2)
                        ->schema([
                            TextInput::make('base_price')
                                ->label('Price (KSh)')
                                ->numeric()
                                ->required(),
                        ]),

                    // Per unit / kg / litre / person share one shape
                    Section::make(fn ($get) => 'Price per '.($get('unit') ?: match ($get('pricing_type')) {
                        PricingType::PER_WEIGHT->value => 'kilogram',
                        PricingType::PER_VOLUME->value => 'litre',
                        PricingType::PER_PERSON->value => 'person',
                        default => 'unit',
                    }))
                        ->visible(fn ($get) => in_array($get('pricing_type'), [
                            PricingType::PER_UNIT->value,
                            PricingType::PER_WEIGHT->value,
                            PricingType::PER_VOLUME->value,
                            PricingType::PER_PERSON->value,
                        ], true))
                        ->columns(2)
                        ->schema([
                            TextInput::make('base_price')
                                ->label('Unit price (KSh)')
                                ->numeric()
                                ->required(),

                            TextInput::make('unit')
                                ->label('Unit')
                                ->placeholder('kg, litre, person, piece...')
                                ->maxLength(20)
                                ->required(),

                            TextInput::make('minimum_quantity')
                                ->label(fn ($get) => 'Minimum '.($get('unit') ?: 'quantity'))
                                ->numeric()
                                ->minValue(0),

                            TextInput::make('maximum_quantity')
                                ->label(fn ($get) => 'Maximum '.($get('unit') ?: 'quantity'))
                                ->numeric()
                                ->minValue(0),
                        ]),

                    // Tiered pricing
                    Section::make('Tiered Pricing')
                        ->visible(fn ($get) => $get('pricing_type') === PricingType::TIERED->value)
                        ->schema([
                            TextInput::make('unit')
                                ->label('Unit')
                                ->placeholder('person, litre, kg...')
                                ->maxLength(20),

                            Repeater::make('tiers')
                                ->relationship()
                                ->label('Price brackets')
                                ->helperText("The engine picks the bracket matching the customer's quantity. Quantities above the highest bracket receive a custom quotation.")
                                ->columns(4)
                                ->schema([
                                    TextInput::make('min_quantity')->numeric()->required(),
                                    TextInput::make('max_quantity')->numeric()->helperText('Empty = and above'),
                                    TextInput::make('unit_price')->label('Price per unit (KSh)')->numeric()->required(),
                                    TextInput::make('label')->maxLength(100),
                                ])
                                ->reorderable(false)
                                ->defaultItems(0),
                        ]),

                    // Custom quotation
                    Section::make('Custom Quotation')
                        ->visible(fn ($get) => $get('pricing_type') === PricingType::CUSTOM->value)
                        ->schema([
                            Placeholder::make('custom_note')
                                ->content('No automatic price is calculated. Customers will submit a request and PMB will provide a quotation.'),
                        ]),
                ]),
        ];
    }
}
