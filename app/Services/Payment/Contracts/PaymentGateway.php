<?php

namespace App\Services\Payment\Contracts;

use App\Models\Payment;
use App\Services\Payment\Data\PaymentResult;

interface PaymentGateway
{
    public function initiateMpesa(Payment $payment, string $phone): PaymentResult;

    public function verifyStatus(Payment $payment): PaymentResult;

    public function checkMpesaStatus(string $checkoutRequestId): PaymentResult;
}
