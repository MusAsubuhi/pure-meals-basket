<?php

namespace App\Jobs;

use App\Enums\Payment\PaymentMethod;
use App\Enums\Payment\PaymentStatus;
use App\Models\Payment;
use App\Services\Payment\PaymentOrchestrator;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class VerifyStaleMpesaPaymentsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(PaymentOrchestrator $orchestrator): void
    {
        $staleThreshold = now()->subHours(2);

        $paymentIds = Payment::query()
            ->where('method', PaymentMethod::MPESA)
            ->where('status', PaymentStatus::PROCESSING)
            ->where('updated_at', '<', $staleThreshold)
            ->limit(100)
            ->pluck('id');

        foreach ($paymentIds as $paymentId) {
            VerifyPendingMpesaPaymentJob::dispatch(Payment::find($paymentId));
        }
    }
}
