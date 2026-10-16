<?php

namespace App\Console;

use App\Jobs\ExpireQuotationsJob;
use App\Jobs\VerifyStaleMpesaPaymentsJob;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Withdraw quotations past their valid_until date.
        $schedule->job(ExpireQuotationsJob::class)->daily();

        // Reconcile M-Pesa payments that never received a callback.
        $schedule->job(VerifyStaleMpesaPaymentsJob::class)->everyTwoHours();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
