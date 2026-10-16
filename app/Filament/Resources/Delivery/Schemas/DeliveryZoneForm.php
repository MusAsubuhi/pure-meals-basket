<?php

namespace App\Filament\Resources\Delivery\Schemas;

use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Numeric;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class DeliveryZoneForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Zone details')
                    ->description('A delivery zone defines where PMB will deliver and how much it costs.')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->label('Zone name')
                            ->placeholder('e.g. Mombasa Island')
                            ->required()
                            ->maxLength(255),

                        TextInput::make('slug')
                            ->label('Slug')
                            ->placeholder('Auto-generated from name')
                            ->maxLength(255)
                            ->helperText('Leave blank to auto-generate from the zone name.'),

                        Textarea::make('description')
                            ->label('Description')
                            ->rows(3)
                            ->columnSpanFull()
                            ->maxLength(1000),

                        Grid::make(2)
                            ->schema([
                                Checkbox::make('is_active')
                                    ->label('Active')
                                    ->default(true)
                                    ->helperText('Inactive zones are hidden from checkout but retained for history.'),

                                Numeric::make('sort_order')
                                    ->label('Sort order')
                                    ->default(0)
                                    ->step(1)
                                    ->helperText('Lower numbers appear first.'),
                            ]),
                    ]),

                Section::make('Pricing')
                    ->description('Delivery charges are calculated per order at checkout.')
                    ->columns(2)
                    ->schema([
                        Numeric::make('base_charge')
                            ->label('Base delivery charge (KSh)')
                            ->default(0)
                            ->step(0.01)
                            ->minValue(0)
                            ->suffix('KSh')
                            ->required()
                            ->helperText('Applied to every order whose address falls inside this zone. e.g. 200 for Ujamaa.'),

                        Numeric::make('door_to_door_surcharge')
                            ->label('Door-to-door surcharge (KSh)')
                            ->default(0)
                            ->step(0.01)
                            ->minValue(0)
                            ->suffix('KSh')
                            ->helperText('Extra charge when the crew delivers directly to the customer\'s door. e.g. 100.'),

                        Numeric::make('beyond_stage_surcharge')
                            ->label('Beyond-stage surcharge (KSh)')
                            ->default(0)
                            ->step(0.01)
                            ->minValue(0)
                            ->suffix('KSh')
                            ->helperText('Extra charge when the drop-off point is past the zone\'s standard stage. e.g. 100.'),

                        Numeric::make('free_delivery_above')
                            ->label('Free delivery above (KSh)')
                            ->nullable()
                            ->step(0.01)
                            ->minValue(0)
                            ->suffix('KSh')
                            ->helperText('Optional — orders at or above this total skip the delivery charge.'),
                    ]),
            ]);
    }
}