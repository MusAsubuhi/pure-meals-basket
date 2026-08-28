<?php

namespace App\Support;

use App\Enums\Order\FulfillmentMethod;
use App\Enums\Order\FulfillmentStatus;
use App\Enums\Order\OrderStatus;
use App\Enums\Order\PaymentStatus as OrderPaymentStatus;
use App\Enums\Payment\PaymentStatus;
use App\Enums\Quotation\QuotationStatus;
use App\Enums\Request\RequestStatus;

/**
 * Translates internal engine statuses into customer-facing language.
 *
 * The modules all store raw enum values (UNDER_REVIEW, PENDING_PAYMENT,
 * OUT_FOR_DELIVERY, ...). Customers should never see those. Every portal
 * view must render a status through one of these helpers so the whole
 * journey speaks one consistent voice.
 */
class CustomerStatus
{
    /* ------------------------------------------------------------------
     * Request
     * ------------------------------------------------------------------ */
    public static function requestLabel(RequestStatus $status): string
    {
        return match ($status) {
            RequestStatus::DRAFT => 'Draft',
            RequestStatus::SUBMITTED => 'Request submitted',
            RequestStatus::UNDER_REVIEW => 'PMB is reviewing',
            RequestStatus::NEEDS_INFORMATION => 'We need more information',
            RequestStatus::QUOTATION_REQUIRED => 'Preparing your quotation',
            RequestStatus::READY_FOR_CHECKOUT => 'Quotation ready',
            RequestStatus::DECLINED => 'Request declined',
            RequestStatus::CANCELLED => 'Cancelled',
        };
    }

    public static function requestBadge(RequestStatus $status): string
    {
        return match ($status) {
            RequestStatus::DRAFT => 'neutral',
            RequestStatus::SUBMITTED => 'blue',
            RequestStatus::UNDER_REVIEW => 'gold',
            RequestStatus::NEEDS_INFORMATION => 'orange',
            RequestStatus::QUOTATION_REQUIRED => 'purple',
            RequestStatus::READY_FOR_CHECKOUT => 'green',
            RequestStatus::DECLINED => 'red',
            RequestStatus::CANCELLED => 'red',
        };
    }

    /* ------------------------------------------------------------------
     * Quotation
     * ------------------------------------------------------------------ */
    public static function quotationLabel(QuotationStatus $status): string
    {
        return match ($status) {
            QuotationStatus::DRAFT => 'Draft',
            QuotationStatus::SENT => 'Quotation ready',
            QuotationStatus::ACCEPTED => 'Accepted',
            QuotationStatus::DECLINED => 'Declined',
            QuotationStatus::WITHDRAWN => 'Withdrawn',
            QuotationStatus::EXPIRED => 'Expired',
        };
    }

    public static function quotationBadge(QuotationStatus $status): string
    {
        return match ($status) {
            QuotationStatus::DRAFT => 'neutral',
            QuotationStatus::SENT => 'green',
            QuotationStatus::ACCEPTED => 'green',
            QuotationStatus::DECLINED => 'red',
            QuotationStatus::WITHDRAWN => 'orange',
            QuotationStatus::EXPIRED => 'neutral',
        };
    }

    /* ------------------------------------------------------------------
     * Order
     * ------------------------------------------------------------------ */
    public static function orderLabel(OrderStatus $status): string
    {
        return match ($status) {
            OrderStatus::DRAFT => 'Draft',
            OrderStatus::PENDING_PAYMENT => 'Payment required',
            OrderStatus::CONFIRMED => 'Order confirmed',
            OrderStatus::PREPARING => 'Being prepared',
            OrderStatus::READY => 'Ready',
            OrderStatus::OUT_FOR_DELIVERY => 'On the way',
            OrderStatus::DELIVERED => 'Delivered',
            OrderStatus::COMPLETED => 'Completed',
            OrderStatus::CANCELLED => 'Cancelled',
        };
    }

    public static function orderBadge(OrderStatus $status): string
    {
        return match ($status) {
            OrderStatus::DRAFT => 'neutral',
            OrderStatus::PENDING_PAYMENT => 'orange',
            OrderStatus::CONFIRMED => 'blue',
            OrderStatus::PREPARING => 'purple',
            OrderStatus::READY => 'green',
            OrderStatus::OUT_FOR_DELIVERY => 'blue',
            OrderStatus::DELIVERED => 'green',
            OrderStatus::COMPLETED => 'green',
            OrderStatus::CANCELLED => 'red',
        };
    }

    /* ------------------------------------------------------------------
     * Order payment status (paid / partial / unpaid)
     * ------------------------------------------------------------------ */
    public static function orderPaymentLabel(OrderPaymentStatus $status): string
    {
        return match ($status) {
            OrderPaymentStatus::UNPAID => 'Unpaid',
            OrderPaymentStatus::PARTIALLY_PAID => 'Partially paid',
            OrderPaymentStatus::PAID => 'Paid in full',
        };
    }

    public static function orderPaymentBadge(OrderPaymentStatus $status): string
    {
        return match ($status) {
            OrderPaymentStatus::UNPAID => 'red',
            OrderPaymentStatus::PARTIALLY_PAID => 'orange',
            OrderPaymentStatus::PAID => 'green',
        };
    }

    /* ------------------------------------------------------------------
     * Payment (the payment entity)
     * ------------------------------------------------------------------ */
    public static function paymentLabel(PaymentStatus $status): string
    {
        return match ($status) {
            PaymentStatus::PENDING => 'Payment pending',
            PaymentStatus::PROCESSING => 'Processing',
            PaymentStatus::SUCCESS => 'Paid',
            PaymentStatus::FAILED => 'Failed',
            PaymentStatus::CANCELLED => 'Cancelled',
            PaymentStatus::REVERSED => 'Reversed',
        };
    }

    public static function paymentBadge(PaymentStatus $status): string
    {
        return match ($status) {
            PaymentStatus::PENDING => 'neutral',
            PaymentStatus::PROCESSING => 'gold',
            PaymentStatus::SUCCESS => 'green',
            PaymentStatus::FAILED => 'red',
            PaymentStatus::CANCELLED => 'red',
            PaymentStatus::REVERSED => 'orange',
        };
    }

    /* ------------------------------------------------------------------
     * Fulfillment
     * ------------------------------------------------------------------ */
    public static function fulfillmentLabel(FulfillmentStatus $status): string
    {
        return match ($status) {
            FulfillmentStatus::PENDING => 'Pending',
            FulfillmentStatus::PREPARING => 'Being prepared',
            FulfillmentStatus::READY => 'Ready',
            FulfillmentStatus::OUT_FOR_DELIVERY => 'On the way',
            FulfillmentStatus::DELIVERED => 'Delivered',
            FulfillmentStatus::COLLECTED => 'Collected',
            FulfillmentStatus::SERVICE_IN_PROGRESS => 'Service in progress',
            FulfillmentStatus::COMPLETED => 'Completed',
            FulfillmentStatus::DELIVERY_FAILED => 'Delivery issue',
            FulfillmentStatus::CANCELLED => 'Cancelled',
        };
    }

    public static function fulfillmentBadge(FulfillmentStatus $status): string
    {
        return match ($status) {
            FulfillmentStatus::PENDING => 'neutral',
            FulfillmentStatus::PREPARING => 'gold',
            FulfillmentStatus::READY => 'green',
            FulfillmentStatus::OUT_FOR_DELIVERY => 'blue',
            FulfillmentStatus::DELIVERED => 'green',
            FulfillmentStatus::COLLECTED => 'green',
            FulfillmentStatus::SERVICE_IN_PROGRESS => 'blue',
            FulfillmentStatus::COMPLETED => 'green',
            FulfillmentStatus::DELIVERY_FAILED => 'red',
            FulfillmentStatus::CANCELLED => 'red',
        };
    }

    public static function methodLabel(FulfillmentMethod $method): string
    {
        return match ($method) {
            FulfillmentMethod::DELIVERY => 'Delivery',
            FulfillmentMethod::CUSTOMER_COLLECTION => 'Collection',
            FulfillmentMethod::ON_SITE_SERVICE => 'On-site service',
        };
    }
}
