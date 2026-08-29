<?php

namespace App\Filament\Resources\Catalogue\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Category Details')
                ->columns(2)
                ->columnSpanFull()
                ->schema([
                    TextInput::make('name')
                        ->label('Name')
                        ->required()
                        ->maxLength(255)
                        ->unique(ignoreRecord: true),

                    TextInput::make('slug')
                        ->label('Slug (auto-generated if empty)')
                        ->maxLength(255)
                        ->unique(ignoreRecord: true),

                    Textarea::make('description')
                        ->columnSpanFull()
                        ->rows(3),

                    FileUpload::make('image_path')
                        ->label('Image')
                        ->image()
                        ->disk('public')
                        ->directory('catalogue/categories')
                        ->maxSize(2048),

                    Toggle::make('is_active')
                        ->label('Active')
                        ->default(true)
                        ->inline(false)
                        ->helperText('Inactive categories do not accept new customer requests.'),

                    TextInput::make('sort_order')
                        ->numeric()
                        ->integer()
                        ->default(0),
                ]),
        ]);
    }
}
