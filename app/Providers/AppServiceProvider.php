<?php

namespace App\Providers;

use App\Services\Payment\Contracts\PaymentGateway;
use App\Services\Payment\Gateways\PayNexusGateway;
use App\Services\Payment\PaymentOrchestrator;
use App\Services\Request\RequestOrchestrator;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(RequestOrchestrator::class);

        $this->app->singleton(PaymentGateway::class, PayNexusGateway::class);
        $this->app->singleton(PaymentOrchestrator::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
