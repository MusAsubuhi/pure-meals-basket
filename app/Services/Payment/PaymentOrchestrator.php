<?php

namespace App\Services\Payment;

use App\Enums\Payment\PaymentMethod;
use App\Enums\Payment\PaymentProvider;
use App\Enums\Payment\PaymentStatus;
use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentAttempt;
use App\Services\Customer\CustomerAccountingService;
use App\Services\Order\OrderOrchestrator;
use App\Services\Payment\Contracts\PaymentGateway;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class PaymentOrchestrator
{
    public function __construct(
        protected PaymentGateway $gateway,
        protected OrderOrchestrator $orderOrchestrator,
        protected CustomerAccountingService $accounting,
    ) {}

    /**
     * Initiate M-Pesa payment for an order.
     */
    public function initiateMpesa(Order $order, string $phone, ?int $userId = null, ?float $amount = null): Payment
    {
        if (! $order->canBeConfirmed()) {
            throw new RuntimeException('Order is not eligible for payment.');
        }

        $amount = $amount ?? $order->balance_due;

        if ($amount < $order->payment_required) {
            throw new RuntimeException('Minimum payment is KSh '.number_format($order->payment_required, 2).'.');
        }

        if ($amount > $order->balance_due) {
            throw new RuntimeException('Payment amount cannot exceed the balance due of KSh '.number_format($order->balance_due, 2).'.');
        }

        if ($amount <= 0) {
            throw new RuntimeException('Payment amount must be greater than zero.');
        }

        return DB::transaction(function () use ($order, $phone, $amount, $userId) {
            $payment = new Payment([
                'order_id' => $order->id,
                'customer_id' => $order->request->customer_id,
                'reference' => Payment::generateReference(),
                'method' => PaymentMethod::MPESA,
                'provider' => PaymentProvider::MPESA,
                'status' => PaymentStatus::PENDING,
                'amount' => $amount,
                'currency' => 'KES',
                'created_by' => $userId,
            ]);
            $payment->save();

            $attempt = new PaymentAttempt([
                'payment_id' => $payment->id,
                'provider' => PaymentProvider::MPESA->value,
                'status' => 'pending',
                'initiated_at' => now(),
            ]);
            $attempt->save();

            $payment->logEvent('INITIATED', 'M-Pesa payment initiated.', $userId, [
                'phone' => $phone,
                'amount' => $amount,
            ]);

            $result = $this->gateway->initiateMpesa($payment, $phone);

            if ($result->success) {
                $payment->update([
                    'status' => PaymentStatus::PROCESSING,
                    'provider_payment_id' => $result->providerPaymentId,
                    'provider_reference' => $result->providerReference,
                    'checkout_request_id' => $result->checkoutRequestId,
                ]);

                $attempt->update([
                    'status' => 'processing',
                    'provider_reference' => $result->providerReference,
                    'checkout_request_id' => $result->checkoutRequestId,
                    'response_payload' => $result->rawResponse,
                ]);

                $payment->logEvent('PROCESSING', 'STK Push sent to customer.', $userId, [
                    'checkout_request_id' => $result->checkoutRequestId,
                    'provider_reference' => $result->providerReference,
                ]);
            } else {
                $payment->update([
                    'status' => PaymentStatus::FAILED,
                ]);

                $attempt->update([
                    'status' => 'failed',
                    'completed_at' => now(),
                    'response_payload' => $result->rawResponse,
                ]);

                $payment->logEvent('FAILED', 'Payment initiation failed: '.$result->message, $userId);
            }

            return $payment->refresh();
        });
    }

    /**
     * Retry a failed M-Pesa payment by creating a new payment record
     * and re-initiating the STK push with the same details.
     */
    public function retryMpesa(Payment $failedPayment, ?int $userId = null): Payment
    {
        if (! $failedPayment->isTerminal() || ! $failedPayment->isFailed()) {
            throw new RuntimeException('Only failed payments can be retried.');
        }

        $order = $failedPayment->order;

        if (! $order->canBeConfirmed()) {
            throw new RuntimeException('Order is not eligible for payment.');
        }

        $amount = $failedPayment->amount;
        $phone = $failedPayment->events()->where('event_type', 'INITIATED')->first()?->data['phone'] ?? null;

        if (! $phone) {
            throw new RuntimeException('Original phone number not found. Please initiate a new payment.');
        }

        return DB::transaction(function () use ($order, $phone, $amount, $userId, $failedPayment) {
            $payment = new Payment([
                'order_id' => $order->id,
                'customer_id' => $order->request->customer_id,
                'reference' => Payment::generateReference(),
                'method' => PaymentMethod::MPESA,
                'provider' => PaymentProvider::MPESA,
                'status' => PaymentStatus::PENDING,
                'amount' => $amount,
                'currency' => 'KES',
                'created_by' => $userId,
            ]);
            $payment->save();

            $attempt = new PaymentAttempt([
                'payment_id' => $payment->id,
                'provider' => PaymentProvider::MPESA->value,
                'status' => 'pending',
                'initiated_at' => now(),
            ]);
            $attempt->save();

            $payment->logEvent('INITIATED', 'M-Pesa payment retried from failed payment '.$failedPayment->reference.'.', $userId, [
                'phone' => $phone,
                'amount' => $amount,
                'retry_of' => $failedPayment->reference,
            ]);

            $result = $this->gateway->initiateMpesa($payment, $phone);

            if ($result->success) {
                $payment->update([
                    'status' => PaymentStatus::PROCESSING,
                    'provider_payment_id' => $result->providerPaymentId,
                    'provider_reference' => $result->providerReference,
                    'checkout_request_id' => $result->checkoutRequestId,
                ]);

                $attempt->update([
                    'status' => 'processing',
                    'provider_reference' => $result->providerReference,
                    'checkout_request_id' => $result->checkoutRequestId,
                    'response_payload' => $result->rawResponse,
                ]);

                $payment->logEvent('PROCESSING', 'STK Push sent to customer (retry).', $userId, [
                    'checkout_request_id' => $result->checkoutRequestId,
                    'provider_reference' => $result->providerReference,
                ]);
            } else {
                $payment->update([
                    'status' => PaymentStatus::FAILED,
                ]);

                $attempt->update([
                    'status' => 'failed',
                    'completed_at' => now(),
                    'response_payload' => $result->rawResponse,
                ]);

                $payment->logEvent('FAILED', 'Payment retry failed: '.$result->message, $userId);
            }

            return $payment->refresh();
        });
    }
    public function recordCash(Order $order, ?int $userId = null, ?float $amount = null): Payment
    {
        if (! $order->canBeConfirmed()) {
            throw new RuntimeException('Order is not eligible for payment.');
        }

        $amount = $amount ?? $order->balance_due;

        if ($amount < $order->payment_required) {
            throw new RuntimeException('Minimum payment is KSh '.number_format($order->payment_required, 2).'.');
        }

        if ($amount > $order->balance_due) {
            throw new RuntimeException('Payment amount cannot exceed the balance due of KSh '.number_format($order->balance_due, 2).'.');
        }

        if ($amount <= 0) {
            throw new RuntimeException('Payment amount must be greater than zero.');
        }

        return DB::transaction(function () use ($order, $amount, $userId) {
            $payment = new Payment([
                'order_id' => $order->id,
                'customer_id' => $order->request->customer_id,
                'reference' => Payment::generateReference(),
                'method' => PaymentMethod::CASH,
                'provider' => PaymentProvider::CASH,
                'status' => PaymentStatus::PENDING,
                'amount' => $amount,
                'currency' => 'KES',
                'created_by' => $userId,
            ]);
            $payment->save();

            $payment->logEvent('INITIATED', 'Cash payment recorded, awaiting staff confirmation.', $userId);

            return $payment->refresh();
        });
    }

    /**
     * Confirm a cash payment (staff only).
     *
     * Idempotent: re-entrant calls for an already-successful payment are
     * no-ops. The payment row is locked so a double-confirm cannot interleave
     * with a concurrent reconciliation.
     */
    public function confirmCash(Payment $payment, ?int $userId = null): Payment
    {
        if ($payment->method !== PaymentMethod::CASH) {
            throw new RuntimeException('Only cash payments can be confirmed via this method.');
        }

        if ($payment->isTerminal()) {
            throw new RuntimeException('Payment is already in a terminal state.');
        }

        return DB::transaction(function () use ($payment, $userId) {
            $locked = Payment::where('id', $payment->id)->lockForUpdate()->first();

            if ($locked === null) {
                throw new RuntimeException('Payment not found during cash confirmation.');
            }

            if ($locked->isSuccess()) {
                return $locked->refresh();
            }

            $locked->update([
                'status' => PaymentStatus::SUCCESS,
                'paid_at' => now(),
            ]);

            $locked->logEvent('SUCCESS', 'Cash payment confirmed by staff.', $userId);

            $this->accounting->recordPayment(
                $locked->order->request->customer,
                $locked->reference,
                $locked->amount,
                'Cash payment confirmed for order '.$locked->order->reference
            );

            $this->applyPaymentToOrder($locked);

            return $locked->refresh();
        });
    }

    /**
     * Handle incoming provider success (webhook or verification).
     *
     * Idempotent: re-entrant calls for the same provider reference are
     * safe. The payment row is locked so a double webhook cannot interleave
     * with a concurrent verification; the accounting credit is recorded
     * exactly once by checking the existing SUCCESS event.
     */
    public function handleProviderSuccess(Payment $payment, array $payload = []): Payment
    {
        if ($payment->isTerminal() && $payment->isSuccess()) {
            return $payment;
        }

        return DB::transaction(function () use ($payment, $payload) {
            $locked = Payment::where('id', $payment->id)->lockForUpdate()->first();

            if ($locked === null) {
                throw new RuntimeException('Payment not found during provider success handling.');
            }

            if ($locked->isSuccess()) {
                // Already processed by a concurrent call — return as-is.
                return $locked->refresh();
            }

            $providerRef = $payload['provider_reference'] ?? $locked->provider_reference;

            $locked->update([
                'status' => PaymentStatus::SUCCESS,
                'paid_at' => now(),
                'provider_payment_id' => $payload['provider_payment_id'] ?? $locked->provider_payment_id,
                'provider_reference' => $providerRef,
            ]);

            $locked->logEvent('SUCCESS', 'Payment confirmed by provider.', null, $payload);

            // Record the accounting credit exactly once per payment. The
            // unique reference on customer_transactions prevents a duplicate
            // from slipping through even if this code path is re-entered.
            $this->accounting->recordPayment(
                $locked->order->request->customer,
                $locked->reference,
                $locked->amount,
                'M-Pesa payment confirmed for order '.$locked->order->reference
            );

            $this->applyPaymentToOrder($locked);

            return $locked->refresh();
        });
    }

    /**
     * Handle incoming provider failure.
     *
     * Idempotent: re-entrant calls for an already-terminal payment are no-ops.
     */
    public function handleProviderFailure(Payment $payment, ?string $reason = null, array $payload = []): Payment
    {
        if ($payment->isTerminal()) {
            return $payment;
        }

        return DB::transaction(function () use ($payment, $reason, $payload) {
            $locked = Payment::where('id', $payment->id)->lockForUpdate()->first();

            if ($locked === null) {
                throw new RuntimeException('Payment not found during provider failure handling.');
            }

            if ($locked->isTerminal()) {
                return $locked->refresh();
            }

            $locked->update([
                'status' => PaymentStatus::FAILED,
            ]);

            $locked->logEvent('FAILED', 'Payment failed: '.($reason ?? 'Unknown'), null, $payload);

            return $locked->refresh();
        });
    }

    /**
     * Verify a processing payment's status with the provider.
     */
    public function verifyPayment(Payment $payment): Payment
    {
        if (! $payment->isProcessing()) {
            return $payment;
        }

        $result = $this->gateway->verifyStatus($payment);

        if (! $result->success) {
            return $payment;
        }

        return match ($result->status) {
            PaymentStatus::SUCCESS->value => $this->handleProviderSuccess($payment, $result->rawResponse),
            PaymentStatus::FAILED->value => $this->handleProviderFailure($payment, $result->message, $result->rawResponse),
            default => $payment,
        };
    }

    /**
     * Aggregate successful payments and apply to order.
     */
    protected function applyPaymentToOrder(Payment $payment): void
    {
        $order = $payment->order;

        $totalPaid = $order->payments()
            ->where('status', PaymentStatus::SUCCESS)
            ->sum('amount');

        $order->update([
            'amount_paid' => $totalPaid,
            'balance_due' => max(0, $order->payment_required - $totalPaid),
        ]);

        if ($totalPaid >= $order->payment_required) {
            $this->orderOrchestrator->confirmAfterPayment($order);
        }
    }
}
