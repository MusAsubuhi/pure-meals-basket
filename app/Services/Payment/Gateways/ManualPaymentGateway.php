<?php

namespace App\Services\Payment\Gateways;

use App\Models\Payment;
use App\Services\Payment\Contracts\PaymentGateway;
use App\Services\Payment\Data\PaymentResult;

/**
 * Default gateway: no online payment provider is connected.
 *
 * Payments are recorded and confirmed by staff. Replace this binding in
 * AppServiceProvider with a real implementation (e.g. Daraja or Pesapal)
 * when online STK push is required.
 */
class ManualPaymentGateway implements PaymentGateway
{
    private const MESSAGE = 'Online M-Pesa is not enabled. Please pay using the manual payment instructions and PMB will confirm your payment.';

    public function initiateMpesa(Payment $payment, string $phone): PaymentResult
    {
        return PaymentResult::failed(self::MESSAGE);
    }

    public function verifyStatus(Payment $payment): PaymentResult
    {
        return PaymentResult::failed(self::MESSAGE);
    }

    public function checkMpesaStatus(string $checkoutRequestId): PaymentResult
    {
        return PaymentResult::failed(self::MESSAGE);
    }
}
