<?php

namespace App\Console\Commands;

use App\Enums\Payment\PaymentMethod;
use App\Enums\Payment\PaymentStatus;
use App\Models\Payment;
use App\Services\Payment\PaymentOrchestrator;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ReconcilePaymentsCommand extends Command
{
    protected $signature = 'payments:reconcile
                            {--hours=24 : Look back window in hours}
                            {--dry-run : Show what would be reconciled without making changes}';

    protected $description = 'Reconcile payment records against provider statuses';

    public function handle(): int
    {
        $hours = (int) $this->option('hours');
        $dryRun = $this->option('dry-run');

        $from = now()->subHours($hours);

        $payments = Payment::query()
            ->where('created_at', '>=', $from)
            ->whereIn('status', [
                PaymentStatus::PENDING,
                PaymentStatus::PROCESSING,
            ])
            ->with(['order'])
            ->get();

        $this->info("Found {$payments->count()} payments to reconcile in the last {$hours} hour(s).");

        $reconciled = 0;
        $errors = 0;

        foreach ($payments as $payment) {
            if ($payment->method === PaymentMethod::CASH) {
                $this->line("  [{$payment->reference}] CASH pending — awaiting staff confirmation");

                continue;
            }

            if ($dryRun) {
                $this->line("  [{$payment->reference}] Would verify M-Pesa status");
                $reconciled++;

                continue;
            }

            try {
                DB::transaction(function () use ($payment) {
                    app(PaymentOrchestrator::class)->verifyPayment($payment->refresh());
                });
                $this->line("  [{$payment->reference}] Verified successfully");
                $reconciled++;
            } catch (\Throwable $e) {
                $this->error("  [{$payment->reference}] Error: ".$e->getMessage());
                $errors++;
            }
        }

        $this->newLine();
        $this->info("Reconciled: {$reconciled}");
        if ($errors > 0) {
            $this->warn("Errors: {$errors}");
        }

        return $errors > 0 ? 1 : 0;
    }
}
