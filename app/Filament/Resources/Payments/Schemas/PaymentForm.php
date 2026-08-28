<?php

namespace App\Filament\Resources\Payments\Schemas;

use App\Enums\Payment\PaymentMethod;
use App\Enums\Payment\PaymentProvider;
use App\Enums\Payment\PaymentStatus;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PaymentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Payment Details')
                    ->columns(2)
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('reference')
                            ->label('Reference')
                            ->disabled()
                            ->dehydrated(false),

                        Select::make('order_id')
                            ->label('Order')
                            ->relationship('order', 'reference')
                            ->searchable()
                            ->preload()
                            ->required(),

                        Select::make('status')
                            ->label('Status')
                            ->options(PaymentStatus::class)
                            ->required(),

                        Select::make('method')
                            ->label('Method')
                            ->options(PaymentMethod::class)
                            ->required(),

                        Select::make('provider')
                            ->label('Provider')
                            ->options(PaymentProvider::class)
                            ->required(),

                        TextInput::make('amount')
                            ->label('Amount')
                            ->numeric()
                            ->prefix('KSh')
                            ->required(),

                        TextInput::make('currency')
                            ->label('Currency')
                            ->disabled()
                            ->dehydrated(false)
                            ->columnSpan(1),

                        TextInput::make('provider_reference')
                            ->label('Provider Reference')
                            ->disabled()
                            ->dehydrated(false)
                            ->columnSpan(1),

                        TextInput::make('checkout_request_id')
                            ->label('Checkout Request ID')
                            ->disabled()
                            ->dehydrated(false)
                            ->columnSpan(1),

                        TextInput::make('paid_at')
                            ->label('Paid At')
                            ->disabled()
                            ->dehydrated(false)
                            ->columnSpan(1),
                    ]),

                Section::make('Notes')
                    ->columnSpanFull()
                    ->schema([
                        Textarea::make('metadata')
                            ->label('Metadata')
                            ->disabled()
                            ->dehydrated(false)
                            ->columnSpanFull()
                            ->formatStateUsing(fn ($state) => $state ? json_encode($state, JSON_PRETTY_PRINT) : ''),
                    ]),
            ]);
    }
}
