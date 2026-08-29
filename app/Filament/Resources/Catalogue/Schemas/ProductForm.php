<?php

namespace App\Filament\Resources\Catalogue\Schemas;

use App\Enums\CatalogStatus;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Product Details')
                ->columns(2)
                ->columnSpanFull()
                ->schema([
                    Select::make('category_id')
                        ->label('Category')
                        ->relationship('category', 'name')
                        ->required()
                        ->preload()
                        ->searchable(),

                    TextInput::make('name')
                        ->label('Product Name')
                        ->required()
                        ->maxLength(255),

                    Textarea::make('short_description')
                        ->label('Short Description')
                        ->maxLength(500)
                        ->columnSpanFull(),

                    Textarea::make('description')
                        ->rows(4)
                        ->columnSpanFull(),

                    FileUpload::make('image_path')
                        ->label('Image')
                        ->image()
                        ->disk('public')
                        ->directory('catalogue/products')
                        ->maxSize(2048),

                    TextInput::make('sort_order')
                        ->numeric()
                        ->integer()
                        ->default(0),
                ]),

            ...PricingFields::make(), // shared dynamic "How is this priced?" sections

            Section::make('Availability & Lifecycle')
                ->columns(3)
                ->columnSpanFull()
                ->schema([
                    Select::make('status')
                        ->options(CatalogStatus::class)
                        ->default(CatalogStatus::DRAFT->value)
                        ->required(),

                    Toggle::make('is_available')
                        ->label('Currently available to customers')
                        ->inline(false)
                        ->default(true)
                        ->helperText('Unavailable items show as "Currently unavailable" but stay listed.'),

                    Toggle::make('requires_review')
                        ->label('Requires PMB review before confirmation')
                        ->inline(false)
                        ->default(false),
                ]),
        ]);
    }
}
