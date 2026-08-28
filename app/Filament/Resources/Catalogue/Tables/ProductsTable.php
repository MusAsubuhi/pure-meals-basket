<?php

namespace App\Filament\Resources\Catalogue\Tables;

use App\Enums\PricingType;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ProductsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with('category'))
            ->columns([
                ImageColumn::make('image_path')
                    ->label('')
                    ->circular()
                    ->defaultImageUrl(fn ($record) => 'https://ui-avatars.com/api/?name='.urlencode($record->name).'&color=8A6D1D'),

                TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->weight('medium'),

                TextColumn::make('category.name')
                    ->label('Category')
                    ->sortable(),

                BadgeColumn::make('pricing_type')
                    ->formatStateUsing(function ($state, $record) {
                        // pricing_type is cast to the PricingType enum on the model
                        $value = $state instanceof PricingType
                            ? $state->value
                            : (string) $state;

                        if (in_array($value, ['per_unit', 'per_weight', 'per_volume', 'per_person'], true)) {
                            $unit = $record->unit ?: '?';

                            return 'KSh '.number_format((float) $record->base_price, 0).' / '.$unit;
                        }

                        return ucfirst(str_replace('_', ' ', $value));
                    })
                    ->colors(['primary' => ['fixed', 'per_unit', 'per_weight', 'per_volume', 'per_person']]),

                IconColumn::make('is_available')
                    ->label('Available')
                    ->boolean(),

                TextColumn::make('status')
                    ->badge(),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->toggleable(isToggledHiddenByDefault: true),
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
