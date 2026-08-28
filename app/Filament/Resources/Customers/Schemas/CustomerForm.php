<?php

namespace App\Filament\Resources\Customers\Schemas;

use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class CustomerForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('User Account')
                    ->description('Login credentials for this customer')
                    ->columns(2)
                    ->schema([
                        TextInput::make('user.name')
                            ->label('Full Name')
                            ->placeholder('e.g Mark Clinton')
                            ->required()
                            ->maxLength(255)
                            ->afterStateHydrated(function (Component $component) {
                                $record = $component->getRecord();
                                if ($record && $record->user) {
                                    $component->state($record->user->name);
                                }
                            })
                            ->dehydrateStateUsing(function ($state, Component $component) {
                                $record = $component->getRecord();
                                if ($state !== null && $record && $record->user) {
                                    $record->user->name = $state;
                                    $record->user->save();
                                }

                                return $state;
                            }),

                        TextInput::make('user.email')
                            ->label('Email')
                            ->required()
                            ->email()
                            ->afterStateHydrated(function (Component $component) {
                                $record = $component->getRecord();
                                if ($record && $record->user) {
                                    $component->state($record->user->email);
                                }
                            })
                            ->dehydrateStateUsing(function ($state, Component $component) {
                                $record = $component->getRecord();
                                if ($state !== null && $record && $record->user) {
                                    $record->user->email = $state;
                                    $record->user->save();
                                }

                                return $state;
                            })
                            ->rules([
                                function ($get) {
                                    $record = $get('record');
                                    $user = $record?->user;

                                    if (! $user || ! $user->isDirty('email')) {
                                        return [];
                                    }

                                    return Rule::unique('users', 'email')->ignore($user->id);
                                },
                            ]),

                        Hidden::make('user.password')
                            ->dehydrateStateUsing(fn ($state) => Hash::make('password'))
                            ->default('password'),

                        TextInput::make('address_line1')
                            ->label('Address Line 1')
                            ->required()
                            ->maxLength(255),

                        TextInput::make('address_line2')
                            ->label('Address Line 2')
                            ->maxLength(255),

                        TextInput::make('city')
                            ->label('City')
                            ->maxLength(255),

                        TextInput::make('state')
                            ->label('State / Region')
                            ->maxLength(255),

                        TextInput::make('postal_code')
                            ->label('Postal Code')
                            ->maxLength(255),

                        TextInput::make('phone')
                            ->label('Phone')
                            ->maxLength(255),

                        TextInput::make('tax_number')
                            ->label('Tax Number')
                            ->maxLength(255),

                        Select::make('status')
                            ->label('Status')
                            ->options([
                                'active' => 'Active',
                                'inactive' => 'Inactive',
                            ])
                            ->default('active')
                            ->required(),
                    ])
                    ->columnSpanFull(),

            ]);
    }
}
