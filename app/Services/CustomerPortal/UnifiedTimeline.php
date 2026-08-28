<?php

namespace App\Services\CustomerPortal;

use App\Models\Order;
use App\Models\Request\Request;
use Carbon\CarbonInterface;

/**
 * Builds the single cross-engine view of a customer's journey.
 *
 * The engines each keep their own audit trail (RequestEvent, QuotationEvent,
 * OrderEvent, PaymentEvent, FulfillmentEvent). This service merges them into
 * one chronological timeline and derives the canonical "YOUR PMB JOURNEY"
 * stepper so the customer never has to understand the module boundaries.
 */
class UnifiedTimeline
{
    /** Map raw event_type -> customer-friendly title. */
    protected array $titles = [
        'REQUEST_CREATED' => 'Request draft created',
        'REQUEST_SUBMITTED' => 'Request submitted',
        'SUBMITTED' => 'Request submitted',
        'UNDER_REVIEW' => 'PMB started reviewing your request',
        'NEEDS_INFORMATION' => 'PMB asked for more information',
        'CLARIFICATION_ANSWERED' => 'Your response received',
        'QUOTATION_REQUIRED' => 'Preparing your quotation',
        'QUOTATION_CREATED' => 'Quotation prepared',
        'QUOTATION_SENT' => 'Quotation sent',
        'SENT' => 'Quotation sent',
        'QUOTATION_ACCEPTED' => 'Quotation accepted',
        'ACCEPTED' => 'Quotation accepted',
        'QUOTATION_DECLINED' => 'Quotation declined',
        'DECLINED' => 'Quotation declined',
        'CHANGE_REQUESTED' => 'Changes requested',
        'REPLACEMENT_CREATED' => 'Revised quotation prepared',
        'CREATED' => 'Order created',
        'ORDER_CREATED' => 'Order created',
        'CONFIRMED' => 'Order confirmed',
        'ORDER_CONFIRMED' => 'Order confirmed',
        'PREPARING' => 'Preparing your order',
        'READY' => 'Your order is ready',
        'OUT_FOR_DELIVERY' => 'Your order is on the way',
        'DELIVERED' => 'Your order was delivered',
        'COMPLETED' => 'Order completed',
        'PAYMENT_RECEIVED' => 'Payment received',
        'CANCELLED' => 'Cancelled',
        'INITIATED' => 'Payment started',
        'STK_PUSH_SENT' => 'M-Pesa prompt sent',
        'PROCESSING' => 'Payment processing',
        'SUCCESS' => 'Payment received',
        'FAILED' => 'Payment failed',
        'REVERSED' => 'Payment reversed',
        'CASH_RECORDED' => 'Cash payment recorded',
        'CASH_CONFIRMED' => 'Cash payment confirmed',
        'DISPATCHED' => 'Your order is on the way',
        'COLLECTED' => 'Your order was collected',
        'SERVICE_STARTED' => 'Service started',
        'DELIVERY_FAILED' => 'Delivery issue',
    ];

    /**
     * One chronological list of every meaningful event across all engines.
     *
     * @return array<int, array{at: CarbonInterface, title: string, detail: ?string}>
     */
    public function timeline(Request $request): array
    {
        $events = [];

        foreach ($request->events as $event) {
            $events[] = [
                'at' => $event->created_at,
                'title' => $this->title($event->event_type),
                'detail' => $event->description,
            ];
        }

        foreach ($request->quotations as $quotation) {
            foreach ($quotation->events as $event) {
                $events[] = [
                    'at' => $event->created_at,
                    'title' => $this->title($event->event_type),
                    'detail' => $event->data['description'] ?? null,
                ];
            }
        }

        foreach ($request->orders as $order) {
            foreach ($order->events as $event) {
                $events[] = [
                    'at' => $event->created_at,
                    'title' => $this->title($event->event_type),
                    'detail' => $event->data['description'] ?? null,
                ];
            }
            foreach ($order->payments as $payment) {
                foreach ($payment->events as $event) {
                    $events[] = [
                        'at' => $event->created_at,
                        'title' => $this->title($event->event_type),
                        'detail' => $event->data['description'] ?? null,
                    ];
                }
            }
            if ($order->fulfillment) {
                foreach ($order->fulfillment->events as $event) {
                    $events[] = [
                        'at' => $event->created_at,
                        'title' => $this->title($event->event_type),
                        'detail' => $event->description,
                    ];
                }
            }
        }

        usort($events, fn ($a, $b) => $a['at'] <=> $b['at']);

        return $events;
    }

    protected function title(string $eventType): string
    {
        if (isset($this->titles[$eventType])) {
            return $this->titles[$eventType];
        }

        return ucwords(strtolower(str_replace('_', ' ', $eventType)));
    }

    /**
     * The canonical journey stepper for a request.
     *
     * @return array<int, array{key: string, label: string, state: string, meta: ?string}>
     */
    public function journey(Request $request): array
    {
        $order = $request->orders->first();
        $fulfillment = $order?->fulfillment;

        $reached = $this->reachedLevel($request, $order);

        $stages = [
            'submitted' => 'Request submitted',
            'reviewed' => 'PMB reviewed your request',
            'quotation' => 'Quotation sent',
            'accepted' => 'Quotation accepted',
            'payment' => 'Payment received',
            'confirmed' => 'Order confirmed',
            'preparing' => 'Preparing your order',
            'ready' => 'Ready',
            'delivery' => $fulfillment ? $this->deliveryStepLabel($fulfillment) : 'Delivery',
            'completed' => 'Completed',
        ];

        $out = [];
        $i = 0;
        foreach ($stages as $key => $label) {
            $state = 'pending';
            if ($i < $reached) {
                $state = 'done';
            } elseif ($i === $reached && $reached < count($stages)) {
                $state = 'current';
            }
            $out[] = [
                'key' => $key,
                'label' => $label,
                'state' => $state,
                'meta' => $this->metaForStage($key, $request, $order),
            ];
            $i++;
        }

        if ($order && $order->isCancelled()) {
            foreach ($out as &$step) {
                if (in_array($step['key'], ['confirmed', 'preparing', 'ready', 'delivery', 'completed'])) {
                    $step['state'] = 'pending';
                    $step['label'] = 'Cancelled';
                }
            }
            unset($step);
        }

        return $out;
    }

    protected function reachedLevel(Request $request, ?Order $order): int
    {
        $level = 0;

        if ($request->submitted_at !== null) {
            $level = max($level, 1);
        }
        if ($request->status->isCommercialPhase()) {
            $level = max($level, 2);
        }

        $hasQuotation = $request->quotations->isNotEmpty();
        $hasAccepted = $request->quotations->contains(fn ($q) => $q->isAccepted());

        if ($hasQuotation) {
            $level = max($level, 3);
        }
        if ($hasAccepted || $order !== null) {
            $level = max($level, 4);
        }

        if ($order) {
            if ($order->amount_paid > 0) {
                $level = max($level, 5);
            }
            $level = max($level, match ($order->status->value) {
                'CONFIRMED' => 6,
                'PREPARING' => 7,
                'READY' => 8,
                'OUT_FOR_DELIVERY', 'DELIVERED' => 9,
                'COMPLETED' => 10,
                default => $level,
            });
        }

        return $level;
    }

    protected function metaForStage(string $key, Request $request, ?Order $order): ?string
    {
        return match ($key) {
            'submitted' => $request->submitted_at?->format('M j · g:i A'),
            'quotation' => $this->firstEventTime($request->quotations, ['QUOTATION_SENT', 'SENT']),
            'accepted' => $this->firstEventTime($request->quotations, ['QUOTATION_ACCEPTED', 'ACCEPTED']),
            'payment' => $order ? $this->paymentTime($order) : null,
            'confirmed' => $order ? $this->firstEventTime(collect([$order]), ['CONFIRMED', 'ORDER_CONFIRMED']) : null,
            'delivery' => $order && $order->fulfillment ? $this->deliveryTime($order->fulfillment) : null,
            'completed' => $order ? $this->firstEventTime(collect([$order]), ['COMPLETED']) : null,
            default => null,
        };
    }

    protected function firstEventTime($holders, array $types): ?string
    {
        foreach ($holders as $holder) {
            foreach ($holder->events as $event) {
                if (in_array($event->event_type, $types, true)) {
                    return $event->created_at->format('M j · g:i A');
                }
            }
        }

        return null;
    }

    protected function paymentTime(Order $order): ?string
    {
        foreach ($order->payments as $payment) {
            if ($payment->paid_at) {
                return $payment->paid_at->format('M j · g:i A');
            }
        }

        return null;
    }

    protected function deliveryStepLabel($fulfillment): string
    {
        return match ($fulfillment->method->value) {
            'CUSTOMER_COLLECTION' => 'Collection',
            'ON_SITE_SERVICE' => 'On-site service',
            default => 'Delivery',
        };
    }

    protected function deliveryTime($fulfillment): ?string
    {
        $markers = ['dispatched_at', 'delivered_at', 'collected_at', 'service_started_at', 'completed_at'];
        foreach ($markers as $attr) {
            if ($fulfillment->{$attr}) {
                return $fulfillment->{$attr}->format('M j · g:i A');
            }
        }

        return null;
    }
}
