<?php

namespace App\Filament\Resources\Orders\Schemas;

use App\Enums\Order\FulfillmentMethod;
use App\Enums\Order\OrderStatus;
use App\Enums\Order\PaymentStatus;
use App\Models\Order;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class OrderForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Order Details')
                    ->columns(2)
                    ->columnSpanFull()
                    ->schema([
                        Select::make('request_id')
                            ->label('Request')
                            ->relationship('request', 'reference')
                            ->getOptionLabelUsing(fn ($record) => $record->reference.' — '.($record->customer->user->name ?? 'Unknown'))
                            ->searchable()
                            ->preload()
                            ->required()
                            ->disabled(fn ($context, $record) => $context === 'edit' && $record !== null && ! $record->canBeCancelled()),

                        Select::make('quotation_id')
                            ->label('Quotation')
                            ->relationship('quotation', 'reference')
                            ->searchable()
                            ->preload()
                            ->disabled(),

                        Select::make('status')
                            ->label('Status')
                            ->options(OrderStatus::class)
                            ->required(),

                        Select::make('payment_status')
                            ->label('Payment Status')
                            ->options(PaymentStatus::class)
                            ->required()
                            ->disabled(fn ($context, $record) => $context === 'edit' && $record !== null && $record->isPaid()),

                        Select::make('fulfillment_method')
                            ->label('Fulfillment Method')
                            ->options(FulfillmentMethod::class)
                            ->nullable()
                            ->live()
                            ->disabled(fn ($context, $record) => $context === 'edit' && $record !== null && $record->fulfillment !== null),

                        TextInput::make('delivery_fee')
                            ->label('Delivery Fee')
                            ->numeric()
                            ->prefix('KSh')
                            ->default(0)
                            ->visible(fn ($context, $record) => $context === 'edit' && $record !== null && $record->fulfillment === null)
                            ->disabled(fn ($context, $record) => $context === 'edit' && $record !== null && $record->fulfillment !== null),

                        TextInput::make('reference')
                            ->label('Reference')
                            ->disabled()
                            ->default(fn () => Order::generateReference()),
                    ]),

                Section::make('Event Details')
                    ->columns(2)
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('event_date')
                            ->label('Event Date')
                            ->type('date')
                            ->disabled(),

                        TextInput::make('event_time')
                            ->label('Event Time')
                            ->type('time')
                            ->disabled(),

                        TextInput::make('location')
                            ->label('Location')
                            ->disabled(),

                        TextInput::make('delivery_address')
                            ->label('Delivery Address')
                            ->columnSpanFull()
                            ->disabled(),

                        Textarea::make('delivery_notes')
                            ->label('Delivery Notes')
                            ->rows(2)
                            ->columnSpanFull()
                            ->disabled(),
                    ]),

                Section::make('Customer Information')
                    ->columns(2)
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('customer_name')
                            ->label('Customer Name')
                            ->disabled(),

                        TextInput::make('customer_phone')
                            ->label('Phone')
                            ->disabled(),

                        TextInput::make('customer_email')
                            ->label('Email')
                            ->disabled()
                            ->columnSpan(2),
                    ]),

                Section::make('Financial Summary')
                    ->columns(3)
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('subtotal')
                            ->label('Subtotal')
                            ->numeric()
                            ->prefix('KSh')
                            ->disabled()
                            ->dehydrated(false)
                            ->columnSpan(1),

                        TextInput::make('discount')
                            ->label('Discount')
                            ->numeric()
                            ->prefix('KSh')
                            ->disabled()
                            ->dehydrated(false)
                            ->columnSpan(1),

                        TextInput::make('total')
                            ->label('Total')
                            ->numeric()
                            ->prefix('KSh')
                            ->disabled()
                            ->dehydrated(false)
                            ->columnSpan(1),

                        TextInput::make('payment_required')
                            ->label('Payment Required')
                            ->numeric()
                            ->prefix('KSh')
                            ->disabled()
                            ->dehydrated(false)
                            ->columnSpan(1),

                        TextInput::make('amount_paid')
                            ->label('Amount Paid')
                            ->numeric()
                            ->prefix('KSh')
                            ->disabled()
                            ->dehydrated(false)
                            ->columnSpan(1),

                        TextInput::make('balance_due')
                            ->label('Balance Due')
                            ->numeric()
                            ->prefix('KSh')
                            ->disabled()
                            ->dehydrated(false)
                            ->columnSpan(1),
                    ]),

                Section::make('Notes')
                    ->columnSpanFull()
                    ->schema([
                        Textarea::make('notes')
                            ->label('Order Notes')
                            ->rows(3)
                            ->columnSpanFull()
                            ->disabled(fn ($context, $record) => $context === 'edit' && $record !== null && ! $record->canBeCancelled()),
                    ]),

                Section::make('Line Items')
                    ->description('Order items are snapshots from the accepted quotation.')
                    ->columnSpanFull()
                    ->schema([
                        Repeater::make('items')
                            ->label('Items')
                            ->relationship('items')
                            ->columns(4)
                            ->schema([
                                TextInput::make('name')
                                    ->label('Item')
                                    ->required()
                                    ->columnSpan(2),

                                Textarea::make('description')
                                    ->label('Description')
                                    ->columnSpan(2),

                                TextInput::make('unit_price')
                                    ->label('Unit Price')
                                    ->numeric()
                                    ->prefix('KSh')
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->columnSpan(1),

                                TextInput::make('quantity')
                                    ->label('Quantity')
                                    ->numeric()
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->columnSpan(1),

                                TextInput::make('unit')
                                    ->label('Unit')
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->columnSpan(1),

                                TextInput::make('subtotal')
                                    ->label('Subtotal')
                                    ->numeric()
                                    ->prefix('KSh')
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->columnSpan(1),
                            ])
                            ->reorderable(false)
                            ->defaultItems(0)
                            ->hiddenLabel()
                            ->columnSpanFull()
                            ->disabled(),
                    ]),
            ]);
    }
}
