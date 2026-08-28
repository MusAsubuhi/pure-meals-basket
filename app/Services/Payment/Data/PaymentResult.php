<?php

namespace App\Services\Payment\Data;

class PaymentResult
{
    public function __construct(
        public readonly bool $success,
        public readonly ?string $status,
        public readonly ?string $providerPaymentId,
        public readonly ?string $providerReference,
        public readonly ?string $checkoutRequestId,
        public readonly ?string $message,
        public readonly array $rawResponse,
    ) {}

    public static function success(array $data): self
    {
        return new self(
            success: true,
            status: $data['status'] ?? null,
            providerPaymentId: $data['provider_payment_id'] ?? null,
            providerReference: $data['provider_reference'] ?? null,
            checkoutRequestId: $data['checkout_request_id'] ?? null,
            message: $data['message'] ?? null,
            rawResponse: $data,
        );
    }

    public static function failed(string $message, array $rawResponse = []): self
    {
        return new self(
            success: false,
            status: null,
            providerPaymentId: null,
            providerReference: null,
            checkoutRequestId: null,
            message: $message,
            rawResponse: $rawResponse,
        );
    }
}
