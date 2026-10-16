<?php

namespace App\Services\Order;

use App\Enums\DeliveryType;
use App\Enums\Order\FulfillmentMethod;
use App\Enums\Order\OrderStatus;
use App\Enums\Order\PaymentStatus;
use App\Models\Customer;
use App\Models\DeliveryZone;
use App\Models\Order;
use App\Models\Quotation;
use App\Models\Request\Request;
use App\Services\Customer\CustomerAccountingService;
use App\Services\Fulfillment\FulfillmentOrchestrator;
use App\Services\Notification\NotificationDispatcher;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

class OrderOrchestrator
{
    public function __construct(
        protected OrderCalculator $calculator,
        protected FulfillmentOrchestrator $fulfillmentOrchestrator,
        protected CustomerAccountingService $accounting,
        protected NotificationDispatcher $notifications,
    ) {}

    /**
     * Create an order from an accepted quotation.
     *
     * The quotation row is locked for the duration of the transaction so
     * two concurrent calls cannot both pass the "order does not exist" check.
     * A database-level unique constraint on orders.quotation_id is the
     * ultimate guard (see migration 2026_10_16_*); the lock makes the check
     * deterministic in the meantime.
     */
    public function createFromQuotation(Quotation $quotation, ?int $createdByUserId = null): Order
    {
        if (! $quotation->isAccepted()) {
            throw new InvalidArgumentException('Quotation must be accepted before creating an order.');
        }

        return DB::transaction(function () use ($quotation, $createdByUserId) {
            $lockedQuotation = Quotation::where('id', $quotation->id)->lockForUpdate()->first();

            if ($lockedQuotation === null) {
                throw new RuntimeException('Quotation not found during order creation.');
            }

            if ($lockedQuotation->order()->exists()) {
                throw new RuntimeException('An order already exists for this quotation.');
            }

            $request = $lockedQuotation->request;

            $order = new Order([
                'request_id' => $request->id,
                'quotation_id' => $lockedQuotation->id,
                'reference' => Order::generateReference(),
                'status' => OrderStatus::PENDING_PAYMENT,
                'payment_status' => PaymentStatus::UNPAID,
                'customer_name' => $request->customer->user->name ?? 'Unknown',
                'customer_phone' => $request->customer->phone ?? '',
                'customer_email' => $request->customer->user->email ?? null,
                'event_date' => $request->event_date,
                'event_time' => $request->event_time,
                'location' => $request->location,
                'subtotal' => $lockedQuotation->subtotal,
                'discount' => $lockedQuotation->discount,
                'total' => $lockedQuotation->total,
                'delivery_fee' => 0,
                'payment_required' => $lockedQuotation->total * 0.7,
                'amount_paid' => 0,
                'balance_due' => $lockedQuotation->total,
                'notes' => $lockedQuotation->notes,
                'created_by' => $createdByUserId,
            ]);
            $order->save();

            $this->applyDeliveryZone($order, $request->location);

            foreach ($lockedQuotation->items as $quotationItem) {
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

            $order->logEvent('CREATED', 'Order created from accepted quotation '.$lockedQuotation->reference.'.', $createdByUserId);

            $this->accounting->recordOrderCharge(
                $request->customer,
                $order->reference,
                $order->total,
                'Order created from quotation '.$lockedQuotation->reference
            );

            return $order->refresh();
        });
    }

    /**
     * Create an order directly from a request (bypassing quotation).
     *
     * The request row is locked for the duration of the transaction so two
     * concurrent calls cannot both create an order for the same request.
     * A database-level unique constraint on orders.request_id is the
     * ultimate guard (see migration 2026_10_16_*); the lock makes the check
     * deterministic in the meantime.
     */
    public function createFromRequest(Request $request, ?int $createdByUserId = null): Order
    {
        return DB::transaction(function () use ($request, $createdByUserId) {
            $lockedRequest = Request::where('id', $request->id)->lockForUpdate()->first();

            if ($lockedRequest === null) {
                throw new RuntimeException('Request not found during order creation.');
            }

            if ($lockedRequest->orders()->exists()) {
                throw new RuntimeException('An order already exists for this request.');
            }

            $order = new Order([
                'request_id' => $lockedRequest->id,
                'reference' => Order::generateReference(),
                'status' => OrderStatus::PENDING_PAYMENT,
                'payment_status' => PaymentStatus::UNPAID,
                'customer_name' => $lockedRequest->customer->user->name ?? 'Unknown',
                'customer_phone' => $lockedRequest->customer->phone ?? '',
                'customer_email' => $lockedRequest->customer->user->email ?? null,
                'event_date' => $lockedRequest->event_date,
                'event_time' => $lockedRequest->event_time,
                'location' => $lockedRequest->location,
                'subtotal' => $lockedRequest->items->sum('subtotal'),
                'discount' => 0,
                'total' => $lockedRequest->items->sum('subtotal'),
                'delivery_fee' => 0,
                'payment_required' => $lockedRequest->items->sum('subtotal') * 0.7,
                'amount_paid' => 0,
                'balance_due' => $lockedRequest->items->sum('subtotal'),
                'notes' => $lockedRequest->notes,
                'created_by' => $createdByUserId,
            ]);
            $order->save();

            $this->applyDeliveryZone($order, $lockedRequest->location);

            foreach ($lockedRequest->items as $requestItem) {
                $order->items()->create([
                    'quotation_item_id' => null,
                    'item_type' => $requestItem->item_type,
                    'name' => $requestItem->name,
                    'description' => null,
                    'quantity' => $requestItem->quantity,
                    'unit' => $requestItem->unit,
                    'unit_price' => $requestItem->unit_price,
                    'subtotal' => $requestItem->subtotal,
                    'options' => $requestItem->options,
                    'metadata' => $requestItem->pricing_breakdown,
                ]);
            }

            $order->logEvent('CREATED', 'Order created directly from request '.$lockedRequest->reference.'.', $createdByUserId);

            $this->accounting->recordOrderCharge(
                $lockedRequest->customer,
                $order->reference,
                $order->total,
                'Order created directly from request '.$lockedRequest->reference
            );

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

            $order = $order->refresh();

            // Fire the customer notification outside the transaction so a
            // slow mail provider never blocks the commercial transition.
            $this->notifications->orderConfirmed($order);

            return $order;
        });
    }

    /**
     * Set fulfillment method, delivery zone and delivery type, then create fulfillment.
     *
     * The delivery type selects which tier surcharges apply on top of the
     * zone's base charge (standard, door_to_door, or beyond_stage). If a
     * delivery zone is provided its charge is recalculated; otherwise the
     * caller-supplied deliveryFee is used as-is.
     */
    public function setFulfillment(
        Order $order,
        ?string $method,
        ?float $deliveryFee = 0,
        ?int $userId = null,
        ?DeliveryZone $zone = null,
        ?DeliveryType $type = null,
    ): Order {
        if (! $order->isConfirmed()) {
            throw new RuntimeException('Order must be confirmed before setting fulfillment.');
        }

        if ($order->fulfillment()->exists()) {
            throw new RuntimeException('Fulfillment already exists for this order.');
        }

        return DB::transaction(function () use ($order, $method, $deliveryFee, $userId, $zone, $type) {
            if ($zone !== null && $type !== null) {
                $deliveryFee = $zone->chargeFor($type, $order->subtotal);

                $order->update([
                    'delivery_zone_id' => $zone->id,
                    'delivery_type' => $type->value,
                    'delivery_fee' => $deliveryFee,
                    'total' => $order->subtotal + $deliveryFee - $order->discount,
                    'balance_due' => max(0, $order->subtotal + $deliveryFee - $order->discount - $order->amount_paid),
                ]);
            } else {
                $order->update([
                    'fulfillment_method' => $method,
                    'delivery_fee' => $deliveryFee,
                    'total' => $order->subtotal + $deliveryFee - $order->discount,
                    'balance_due' => max(0, $order->total - $order->amount_paid),
                ]);
            }

            if ($method) {
                $this->fulfillmentOrchestrator->createFromOrder($order->refresh(), $userId);
            }

            $order->logEvent('FULFILLMENT_SET', 'Fulfillment method set to '.($method ?? 'none').' with delivery fee '.number_format($deliveryFee, 2), $userId);

            return $order->refresh();
        });
    }

    /**
     * Change the delivery type for an existing confirmed order before
     * fulfillment is created. Recalculates the delivery fee from the
     * order's assigned zone.
     */
    public function setDeliveryType(Order $order, DeliveryType $type, ?int $userId = null): Order
    {
        if (! $order->isConfirmed()) {
            throw new RuntimeException('Order must be confirmed before setting delivery type.');
        }

        if ($order->fulfillment()->exists()) {
            throw new RuntimeException('Delivery type cannot be changed after fulfillment has been created.');
        }

        if ($order->delivery_zone_id === null) {
            throw new RuntimeException('Order has no delivery zone assigned.');
        }

        return DB::transaction(function () use ($order, $type, $userId) {
            $zone = DeliveryZone::find($order->delivery_zone_id);

            if ($zone === null) {
                throw new RuntimeException('Delivery zone not found for order.');
            }

            $charge = $zone->chargeFor($type, $order->subtotal);

            $order->update([
                'delivery_type' => $type->value,
                'delivery_fee' => $charge,
                'total' => $order->subtotal + $charge - $order->discount,
                'balance_due' => max(0, $order->subtotal + $charge - $order->discount - $order->amount_paid),
            ]);

            $order->logEvent('DELIVERY_TYPE', 'Delivery type set to '.$type->value.' (charge '.number_format($charge, 2).').', $userId);

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

            $order = $order->refresh();

            // Fire the customer notification outside the transaction so a
            // slow mail provider never blocks the commercial transition.
            $this->notifications->orderPreparing($order);

            return $order;
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

            $order = $order->refresh();

            // Fire the customer notification outside the transaction so a
            // slow mail provider never blocks the commercial transition.
            $this->notifications->orderReady($order);

            return $order;
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

            $order = $order->refresh();

            // Fire the customer notification outside the transaction so a
            // slow mail provider never blocks the commercial transition.
            $this->notifications->orderOutForDelivery($order);

            return $order;
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

            $order = $order->refresh();

            // Fire the customer notification outside the transaction so a
            // slow mail provider never blocks the commercial transition.
            $this->notifications->orderDelivered($order);

            return $order;
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

            $order = $order->refresh();

            // Fire the customer notification outside the transaction so a
            // slow mail provider never blocks the commercial transition.
            $this->notifications->orderCompleted($order);

            return $order;
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

    /**
     * Resolve a delivery zone for the order's location and apply its charge.
     *
     * If no active zone matches the location, the order keeps a zero delivery
     * fee. The zone charge is added to the order total and the balance_due is
     * recomputed so the customer sees the real amount owed.
     *
     * The delivery type selects which tier surcharges apply on top of the
     * zone's base charge (standard, door_to_door, or beyond_stage).
     */
    protected function applyDeliveryZone(Order $order, ?string $location, DeliveryType $type = DeliveryType::STANDARD): void
    {
        $zone = DeliveryZone::resolveForLocation($location)->first();

        if ($zone === null) {
            return;
        }

        $charge = $zone->chargeFor($type, $order->subtotal);

        $order->update([
            'delivery_zone_id' => $zone->id,
            'delivery_type' => $type->value,
            'delivery_fee' => $charge,
            'total' => $order->subtotal + $charge - $order->discount,
            'balance_due' => max(0, $order->subtotal + $charge - $order->discount - $order->amount_paid),
        ]);

        $order->logEvent('DELIVERY_ZONE', 'Delivery zone '.$zone->name.' applied (type '.$type->value.', charge '.number_format($charge, 2).').');
    }
}
