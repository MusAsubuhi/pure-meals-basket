<?php

namespace App\Providers;

use App\Listeners\Payment\HandlePayNexusPaymentCompleted;
use App\Listeners\Payment\HandlePayNexusPaymentFailed;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use PayNexus\Events\PaymentCompleted;
use PayNexus\Events\PaymentFailed;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        PaymentCompleted::class => [
            HandlePayNexusPaymentCompleted::class,
        ],
        PaymentFailed::class => [
            HandlePayNexusPaymentFailed::class,
        ],
    ];

    public function boot(): void
    {
        //
    }

    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
