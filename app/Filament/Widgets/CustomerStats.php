<?php

namespace App\Filament\Widgets;

use App\Enums\Order\OrderStatus;
use App\Enums\Payment\PaymentStatus;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Payment;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class CustomerStats extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Total Customers', Customer::count())
                ->description('Registered customers')
                ->icon('heroicon-o-users')
                ->color('success'),

            Stat::make('Total Orders', Order::count())
                ->description('All time orders')
                ->icon('heroicon-o-shopping-cart')
                ->color('info'),

            Stat::make('Total Revenue', 'KSh '.number_format(
                Payment::where('status', PaymentStatus::SUCCESS)
                    ->sum('amount'),
                2
            ))
                ->description('Successful payments')
                ->icon('heroicon-o-currency-dollar')
                ->color('success'),

            Stat::make('Pending Orders', Order::where('status', OrderStatus::PENDING_PAYMENT)->count())
                ->description('Awaiting payment')
                ->icon('heroicon-o-clock')
                ->color('warning'),
        ];
    }
}
