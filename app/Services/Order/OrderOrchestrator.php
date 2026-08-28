<?php

namespace App\Services\Order;

use App\Enums\Order\FulfillmentMethod;
use App\Enums\Order\OrderStatus;
use App\Enums\Order\PaymentStatus;
use App\Models\Order;
use App\Models\Quotation;
use App\Services\Fulfillment\FulfillmentOrchestrator;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

class OrderOrchestrator
{
    public function __construct(
        protected OrderCalculator $calculator,
        protected FulfillmentOrchestrator $fulfillmentOrchestrator,
    ) {}

    /**
     * Create an order from an accepted quotation.
     */
    public function createFromQuotation(Quotation $quotation, ?int $createdByUserId = null): Order
    {
        if (! $quotation->isAccepted()) {
            throw new InvalidArgumentException('Quotation must be accepted before creating an order.');
        }

        if ($quotation->order()->exists()) {
            throw new RuntimeException('An order already exists for this quotation.');
        }

        return DB::transaction(function () use ($quotation, $createdByUserId) {
            $request = $quotation->request;

            $order = new Order([
                'request_id' => $request->id,
                'quotation_id' => $quotation->id,
                'reference' => Order::generateReference(),
                'status' => OrderStatus::PENDING_PAYMENT,
                'payment_status' => PaymentStatus::UNPAID,
                'customer_name' => $request->customer->user->name ?? 'Unknown',
                'customer_phone' => $request->customer->phone ?? '',
                'customer_email' => $request->customer->user->email ?? null,
                'event_date' => $request->event_date,
                'event_time' => $request->event_time,
                'location' => $request->location,
                'subtotal' => $quotation->subtotal,
                'discount' => $quotation->discount,
                'total' => $quotation->total,
                'payment_required' => $quotation->total,
                'amount_paid' => 0,
                'balance_due' => $quotation->total,
                'notes' => $quotation->notes,
                'created_by' => $createdByUserId,
            ]);
            $order->save();

            foreach ($quotation->items as $quotationItem) {
                $order->items()->create([
                    'quotation_item_id' => $quotationItem->id,
                    'item_type' => null,
                    'name' => $quotationItem->name,
                    'description' => $quotationItem->description,
                    'quantity' => $quotationItem->quantity,
                    'unit' => $quotationItem->unit,
                    'unit_price' => $quotationItem->unit_price,
                    'subtotal' => $quotationItem->subtotal,
                    'options' => $quotationItem->metadata,
                ]);
            }

            $order->logEvent('CREATED', 'Order created from accepted quotation '.$quotation->reference.'.', $createdByUserId);

            return $order;
        });
    }

    /**
     * Confirm order after required payment received.
     */
    public function confirmAfterPayment(Order $order, ?int $userId = null): Order
    {
        if (! $order->canBeConfirmed()) {
            throw new RuntimeException('Order cannot be confirmed in its current state.');
        }

        if ($order->amount_paid < $order->payment_required) {
            throw new RuntimeException('Insufficient payment received. Required: '.$order->payment_required.', Paid: '.$order->amount_paid);
        }

        return DB::transaction(function () use ($order, $userId) {
            $order->update([
                'status' => OrderStatus::CONFIRMED,
                'payment_status' => $order->amount_paid >= $order->total ? PaymentStatus::PAID : PaymentStatus::PARTIALLY_PAID,
            ]);

            $order->logEvent('CONFIRMED', 'Order confirmed after payment.', $userId);

            if ($order->fulfillment_method) {
                $this->fulfillmentOrchestrator->createFromOrder($order->refresh(), $userId);
            }

            return $order->refresh();
        });
    }

    /**
     * Cancel an order.
     */
    public function cancel(Order $order, ?int $userId = null): Order
    {
        if (! $order->canBeCancelled()) {
            throw new RuntimeException('Order cannot be cancelled in its current state.');
        }

        return DB::transaction(function () use ($order, $userId) {
            $order->update([
                'status' => OrderStatus::CANCELLED,
            ]);

            $order->logEvent('CANCELLED', 'Order cancelled.', $userId);

            return $order->refresh();
        });
    }

    /**
     * Start preparing an order.
     */
    public function startPreparing(Order $order, ?int $userId = null): Order
    {
        if (! $order->canStartPreparing()) {
            throw new RuntimeException('Order cannot be prepared in its current state.');
        }

        return DB::transaction(function () use ($order, $userId) {
            $order->update([
                'status' => OrderStatus::PREPARING,
            ]);

            $order->logEvent('PREPARING', 'Order preparation started.', $userId);

            if ($order->fulfillment) {
                $this->fulfillmentOrchestrator->startPreparing($order->fulfillment, $userId);
            }

            return $order->refresh();
        });
    }

    /**
     * Mark order as ready.
     */
    public function markReady(Order $order, ?int $userId = null): Order
    {
        if (! $order->canMarkReady()) {
            throw new RuntimeException('Order cannot be marked as ready in its current state.');
        }

        return DB::transaction(function () use ($order, $userId) {
            $order->update([
                'status' => OrderStatus::READY,
            ]);

            $order->logEvent('READY', 'Order is ready.', $userId);

            if ($order->fulfillment) {
                $this->fulfillmentOrchestrator->markReady($order->fulfillment, $userId);
            }

            return $order->refresh();
        });
    }

    /**
     * Dispatch order for delivery.
     */
    public function dispatch(Order $order, ?int $userId = null): Order
    {
        if (! $order->canDispatch()) {
            throw new RuntimeException('Order cannot be dispatched in its current state.');
        }

        if ($order->fulfillment_method !== FulfillmentMethod::DELIVERY) {
            throw new RuntimeException('Only delivery orders can be dispatched.');
        }

        return DB::transaction(function () use ($order, $userId) {
            $order->update([
                'status' => OrderStatus::OUT_FOR_DELIVERY,
            ]);

            $order->logEvent('OUT_FOR_DELIVERY', 'Order dispatched for delivery.', $userId);

            if ($order->fulfillment) {
                $this->fulfillmentOrchestrator->dispatch($order->fulfillment, $userId);
            }

            return $order->refresh();
        });
    }

    /**
     * Mark order as delivered.
     */
    public function markDelivered(Order $order, ?int $userId = null): Order
    {
        if (! $order->canMarkDelivered()) {
            throw new RuntimeException('Order cannot be marked as delivered in its current state.');
        }

        return DB::transaction(function () use ($order, $userId) {
            $order->update([
                'status' => OrderStatus::DELIVERED,
            ]);

            $order->logEvent('DELIVERED', 'Order delivered.', $userId);

            if ($order->fulfillment) {
                $this->fulfillmentOrchestrator->markDelivered($order->fulfillment, null, $userId);
            }

            return $order->refresh();
        });
    }

    /**
     * Complete an order.
     */
    public function complete(Order $order, ?int $userId = null): Order
    {
        if (! $order->canComplete()) {
            throw new RuntimeException('Order cannot be completed in its current state.');
        }

        return DB::transaction(function () use ($order, $userId) {
            $order->update([
                'status' => OrderStatus::COMPLETED,
            ]);

            $order->logEvent('COMPLETED', 'Order completed.', $userId);

            if ($order->fulfillment) {
                $this->fulfillmentOrchestrator->complete($order->fulfillment, $userId);
            }

            return $order->refresh();
        });
    }

    /**
     * Record a payment for an order.
     */
    public function recordPayment(Order $order, float $amount, ?int $userId = null): Order
    {
        if ($order->isTerminal()) {
            throw new RuntimeException('Cannot record payment for a completed or cancelled order.');
        }

        return DB::transaction(function () use ($order, $amount, $userId) {
            $newAmountPaid = $order->amount_paid + $amount;
            $newBalanceDue = max(0, $order->total - $newAmountPaid);

            $order->update([
                'amount_paid' => $newAmountPaid,
                'balance_due' => $newBalanceDue,
                'payment_status' => $newAmountPaid >= $order->total ? PaymentStatus::PAID : PaymentStatus::PARTIALLY_PAID,
            ]);

            $order->logEvent('PAYMENT_RECEIVED', 'Payment of '.$amount.' received.', $userId, [
                'amount' => $amount,
                'total_paid' => $newAmountPaid,
                'balance_due' => $newBalanceDue,
            ]);

            return $order->refresh();
        });
    }
}
