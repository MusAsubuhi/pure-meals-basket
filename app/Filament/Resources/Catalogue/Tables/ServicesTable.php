<?php

namespace App\Filament\Resources\Catalogue\Tables;

use App\Enums\PricingType;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ServicesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with('category'))
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->weight('medium')
                    ->icon('heroicon-o-briefcase'),

                TextColumn::make('category.name')
                    ->label('Category')
                    ->sortable()
                    ->placeholder('—'),

                BadgeColumn::make('pricing_type')
                    ->formatStateUsing(function ($state, $record) {
                        // pricing_type is cast to the PricingType enum on the model
                        $value = $state instanceof PricingType
                            ? $state->value
                            : (string) $state;

                        if (in_array($value, ['per_unit', 'per_weight', 'per_volume', 'per_person'], true)) {
                            return 'KSh '.number_format($record->base_price, 0).' / '.($record->unit ?: '?');
                        }

                        return ucfirst(str_replace('_', ' ', $value));
                    }),

                IconColumn::make('is_available')
                    ->label('Available')
                    ->boolean(),

                TextColumn::make('status')
                    ->badge(),
            ])
            ->filters([
                SelectFilter::make('category')->relationship('category', 'name'),
                SelectFilter::make('status'),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }
}
