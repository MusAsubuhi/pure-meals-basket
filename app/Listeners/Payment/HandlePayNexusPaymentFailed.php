<?php

namespace App\Listeners\Payment;

use App\Models\Payment;
use App\Services\Payment\PaymentOrchestrator;
use Illuminate\Contracts\Queue\ShouldQueue;
use PayNexus\Events\PaymentFailed;

class HandlePayNexusPaymentFailed implements ShouldQueue
{
    public function __construct(
        protected PaymentOrchestrator $orchestrator,
    ) {}

    public function handle(PaymentFailed $event): void
    {
        $paynexusPayment = $event->payment;
        $payload = $event->payload;
        $reason = $event->reason;

        $payment = Payment::where('checkout_request_id', $paynexusPayment->checkout_request_id)
            ->orWhere('reference', $paynexusPayment->reference)
            ->first();

        if (! $payment) {
            return;
        }

        $this->orchestrator->handleProviderFailure($payment, $reason, $payload);
    }
}
