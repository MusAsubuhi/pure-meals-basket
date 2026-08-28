<?php

namespace App\Services\Payment\Gateways;

use App\Enums\Payment\PaymentStatus;
use App\Models\Payment;
use App\Services\Payment\Contracts\PaymentGateway;
use App\Services\Payment\Data\PaymentResult;
use PayNexus\Exceptions\PayNexusApiException;
use PayNexus\Exceptions\PayNexusAuthException;
use PayNexus\Exceptions\PayNexusConnectionException;
use PayNexus\Facades\PayNexus;

class PayNexusGateway implements PaymentGateway
{
    public function initiateMpesa(Payment $payment, string $phone): PaymentResult
    {
        if ($payment->status->isTerminal()) {
            return PaymentResult::failed('Payment is already in a terminal state.');
        }

        try {
            $response = PayNexus::initiateMpesaPayment([
                'amount' => $payment->amount,
                'phone' => $phone,
                'description' => 'Payment for order '.$payment->order->reference,
                'remark' => 'PMB Order Payment',
                'idempotency_key' => $payment->reference,
            ]);

            if (! ($response['success'] ?? false)) {
                return PaymentResult::failed(
                    $response['message'] ?? 'Payment initiation failed.',
                    $response
                );
            }

            $data = $response['data'] ?? $response;

            return PaymentResult::success([
                'status' => PaymentStatus::PROCESSING->value,
                'provider_payment_id' => $data['payment_id'] ?? null,
                'provider_reference' => $data['reference'] ?? null,
                'checkout_request_id' => $data['checkout_request_id'] ?? null,
                'message' => $response['message'] ?? 'STK Push initiated.',
                'raw' => $response,
            ]);
        } catch (PayNexusAuthException $e) {
            return PaymentResult::failed('PayNexus authentication failed.', ['exception' => $e->getMessage()]);
        } catch (PayNexusConnectionException $e) {
            return PaymentResult::failed('Unable to reach PayNexus.', ['exception' => $e->getMessage()]);
        } catch (PayNexusApiException $e) {
            return PaymentResult::failed('PayNexus API error: '.$e->getMessage(), ['exception' => $e->getMessage()]);
        } catch (\Exception $e) {
            return PaymentResult::failed('Unexpected error: '.$e->getMessage(), ['exception' => $e->getMessage()]);
        }
    }

    public function verifyStatus(Payment $payment): PaymentResult
    {
        $checkoutRequestId = $payment->checkout_request_id;

        if (! $checkoutRequestId) {
            return PaymentResult::failed('No checkout request ID available for verification.');
        }

        try {
            $response = PayNexus::checkMpesaStatus($checkoutRequestId);

            if (! ($response['success'] ?? false)) {
                return PaymentResult::failed(
                    $response['message'] ?? 'Status verification failed.',
                    $response
                );
            }

            $data = $response['data'] ?? $response;
            $status = strtoupper($data['status'] ?? 'PENDING');

            $mappedStatus = match ($status) {
                'COMPLETED' => PaymentStatus::SUCCESS,
                'FAILED', 'TIMEOUT' => PaymentStatus::FAILED,
                'CANCELLED' => PaymentStatus::CANCELLED,
                'REVERSED' => PaymentStatus::REVERSED,
                default => PaymentStatus::PROCESSING,
            };

            return PaymentResult::success([
                'status' => $mappedStatus->value,
                'provider_payment_id' => $data['payment_id'] ?? null,
                'provider_reference' => $data['provider_reference'] ?? null,
                'checkout_request_id' => $checkoutRequestId,
                'message' => $response['message'] ?? 'Status retrieved.',
                'raw' => $response,
            ]);
        } catch (\Exception $e) {
            return PaymentResult::failed('Verification error: '.$e->getMessage(), ['exception' => $e->getMessage()]);
        }
    }

    public function checkMpesaStatus(string $checkoutRequestId): PaymentResult
    {
        try {
            $response = PayNexus::checkMpesaStatus($checkoutRequestId);

            if (! ($response['success'] ?? false)) {
                return PaymentResult::failed(
                    $response['message'] ?? 'M-Pesa status check failed.',
                    $response
                );
            }

            $data = $response['data'] ?? $response;
            $status = strtoupper($data['status'] ?? 'PENDING');

            $mappedStatus = match ($status) {
                'COMPLETED' => PaymentStatus::SUCCESS,
                'FAILED', 'TIMEOUT' => PaymentStatus::FAILED,
                'CANCELLED' => PaymentStatus::CANCELLED,
                'REVERSED' => PaymentStatus::REVERSED,
                default => PaymentStatus::PROCESSING,
            };

            return PaymentResult::success([
                'status' => $mappedStatus->value,
                'provider_payment_id' => $data['payment_id'] ?? null,
                'provider_reference' => $data['provider_reference'] ?? null,
                'checkout_request_id' => $checkoutRequestId,
                'message' => $response['message'] ?? 'M-Pesa status retrieved.',
                'raw' => $response,
            ]);
        } catch (\Exception $e) {
            return PaymentResult::failed('M-Pesa status check error: '.$e->getMessage(), ['exception' => $e->getMessage()]);
        }
    }
}
