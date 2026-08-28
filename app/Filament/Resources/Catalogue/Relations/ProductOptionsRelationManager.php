<?php

namespace App\Filament\Resources\Catalogue\Relations;

use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * Options & their values for a product: e.g. Frosting (Buttercream +0,
 * Fondant +800), Decoration (Standard, Premium +1,000).
 */
class ProductOptionsRelationManager extends RelationManager
{
    protected static string $relationship = 'options';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Option')
                ->columns(2)
                ->schema([
                    TextInput::make('name')
                        ->label('Option name')
                        ->placeholder('Frosting, Decoration, Menu...')
                        ->required()
                        ->maxLength(255),

                    Select::make('type')
                        ->label('Input type')
                        ->options(['select' => 'Dropdown'])
                        ->default('select'),

                    Toggle::make('is_required')
                        ->inline(false)
                        ->default(false),

                    TextInput::make('sort_order')->numeric()->integer()->default(0),
                ]),

            Section::make('Values & price modifiers')
                ->description('Each value can add to the calculated price.')
                ->columnSpanFull()
                ->schema([
                    Repeater::make('values')
                        ->relationship()
                        ->columns(4)
                        ->schema([
                            TextInput::make('name')
                                ->placeholder('Buttercream, Fondant...')
                                ->required(),
                            TextInput::make('value')->nullable(),
                            TextInput::make('price_modifier')
                                ->label('+ Price (KSh)')
                                ->numeric()
                                ->default(0),
                            Toggle::make('is_available')->inline(false)->default(true),
                        ])
                        ->reorderable(true)
                        ->defaultItems(0),
                ]),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->withCount('values'))
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('name'),
                IconColumn::make('is_required')->boolean()->label('Required'),
                TextColumn::make('values_count')->counts('values')->label('Values'),
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
