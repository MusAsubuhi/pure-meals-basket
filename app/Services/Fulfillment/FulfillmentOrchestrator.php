<?php

namespace App\Services\Fulfillment;

use App\Enums\Order\FulfillmentMethod;
use App\Enums\Order\FulfillmentStatus;
use App\Models\Fulfillment\Fulfillment;
use App\Models\Order;
use App\Services\Fulfillment\Exceptions\FulfillmentAlreadyExists;
use App\Services\Fulfillment\Exceptions\InvalidFulfillmentMethod;
use App\Services\Fulfillment\Exceptions\InvalidFulfillmentTransition;
use App\Services\Fulfillment\Exceptions\PaymentNotConfirmed;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class FulfillmentOrchestrator
{
    public function createFromOrder(Order $order, ?int $userId = null): Fulfillment
    {
        if (! $order->isConfirmed()) {
            throw new RuntimeException('Order must be confirmed before fulfillment can be created.');
        }

        if ($order->amount_paid < $order->payment_required) {
            throw new PaymentNotConfirmed('Payment has not been confirmed for this order.');
        }

        if (Fulfillment::where('order_id', $order->id)->exists()) {
            throw new FulfillmentAlreadyExists('Fulfillment already exists for this order.');
        }

        if (! $order->fulfillment_method) {
            throw new RuntimeException('Order must have a fulfillment method set.');
        }

        $method = $order->fulfillment_method instanceof FulfillmentMethod
            ? $order->fulfillment_method
            : FulfillmentMethod::from($order->fulfillment_method);

        return DB::transaction(function () use ($order, $method, $userId) {
            $scheduledAt = null;
            if ($order->event_date) {
                $time = $order->event_time ? $order->event_time->format('H:i:s') : '00:00:00';
                $scheduledAt = $order->event_date->format('Y-m-d').' '.$time;
            }

            $fulfillment = new Fulfillment([
                'order_id' => $order->id,
                'method' => $method,
                'status' => FulfillmentStatus::PENDING,
                'scheduled_at' => $scheduledAt,
                'delivery_fee' => $order->delivery_fee ?? 0,
            ]);
            $fulfillment->save();

            $fulfillment->logEvent('FULFILLMENT_CREATED', 'Fulfillment created from order '.$order->reference.'.', $userId, [
                'order_id' => $order->id,
                'method' => $method->value,
            ]);

            return $fulfillment;
        });
    }

    public function startPreparing(Fulfillment $fulfillment, ?int $userId = null): Fulfillment
    {
        if (! $fulfillment->status->canStartPreparing()) {
            throw new InvalidFulfillmentTransition('Fulfillment cannot be prepared in its current state.');
        }

        return DB::transaction(function () use ($fulfillment, $userId) {
            $fulfillment->update([
                'status' => FulfillmentStatus::PREPARING,
                'started_at' => now(),
            ]);

            $fulfillment->logEvent('PREPARATION_STARTED', 'Preparation started.', $userId);

            return $fulfillment->refresh();
        });
    }

    public function markReady(Fulfillment $fulfillment, ?int $userId = null): Fulfillment
    {
        if (! $fulfillment->status->canMarkReady()) {
            throw new InvalidFulfillmentTransition('Fulfillment cannot be marked as ready in its current state.');
        }

        return DB::transaction(function () use ($fulfillment, $userId) {
            $fulfillment->update([
                'status' => FulfillmentStatus::READY,
                'ready_at' => now(),
            ]);

            $fulfillment->logEvent('ORDER_READY', 'Order is ready for fulfillment.', $userId);

            return $fulfillment->refresh();
        });
    }

    public function dispatch(Fulfillment $fulfillment, ?int $userId = null): Fulfillment
    {
        if ($fulfillment->method !== FulfillmentMethod::DELIVERY) {
            throw new InvalidFulfillmentMethod('Only delivery fulfillments can be dispatched.');
        }

        if (! $fulfillment->status->canDispatch()) {
            throw new InvalidFulfillmentTransition('Fulfillment cannot be dispatched in its current state.');
        }

        return DB::transaction(function () use ($fulfillment, $userId) {
            $fulfillment->update([
                'status' => FulfillmentStatus::OUT_FOR_DELIVERY,
                'dispatched_at' => now(),
            ]);

            $fulfillment->logEvent('DISPATCHED', 'Order dispatched for delivery.', $userId);

            return $fulfillment->refresh();
        });
    }

    public function markDelivered(Fulfillment $fulfillment, ?string $recipientName = null, ?int $userId = null): Fulfillment
    {
        if (! $fulfillment->status->canMarkDelivered()) {
            throw new InvalidFulfillmentTransition('Fulfillment cannot be marked as delivered in its current state.');
        }

        return DB::transaction(function () use ($fulfillment, $recipientName, $userId) {
            $fulfillment->update([
                'status' => FulfillmentStatus::DELIVERED,
                'delivered_at' => now(),
                'recipient_name' => $recipientName,
            ]);

            $fulfillment->logEvent('DELIVERED', 'Order delivered.', $userId, [
                'recipient_name' => $recipientName,
            ]);

            return $fulfillment->refresh();
        });
    }

    public function markCollected(Fulfillment $fulfillment, ?string $recipientName = null, ?int $userId = null): Fulfillment
    {
        if (! $fulfillment->status->canMarkCollected()) {
            throw new InvalidFulfillmentTransition('Fulfillment cannot be marked as collected in its current state.');
        }

        return DB::transaction(function () use ($fulfillment, $recipientName, $userId) {
            $fulfillment->update([
                'status' => FulfillmentStatus::COLLECTED,
                'collected_at' => now(),
                'recipient_name' => $recipientName,
            ]);

            $fulfillment->logEvent('COLLECTED', 'Order collected by customer.', $userId, [
                'recipient_name' => $recipientName,
            ]);

            return $fulfillment->refresh();
        });
    }

    public function startService(Fulfillment $fulfillment, ?int $userId = null): Fulfillment
    {
        if (! $fulfillment->status->canStartService()) {
            throw new InvalidFulfillmentTransition('Fulfillment service cannot be started in its current state.');
        }

        return DB::transaction(function () use ($fulfillment, $userId) {
            $fulfillment->update([
                'status' => FulfillmentStatus::SERVICE_IN_PROGRESS,
                'service_started_at' => now(),
            ]);

            $fulfillment->logEvent('SERVICE_STARTED', 'On-site service started.', $userId);

            return $fulfillment->refresh();
        });
    }

    public function complete(Fulfillment $fulfillment, ?int $userId = null): Fulfillment
    {
        if (! $fulfillment->status->canComplete()) {
            throw new InvalidFulfillmentTransition('Fulfillment cannot be completed in its current state.');
        }

        return DB::transaction(function () use ($fulfillment, $userId) {
            $fulfillment->update([
                'status' => FulfillmentStatus::COMPLETED,
                'completed_at' => now(),
            ]);

            $fulfillment->logEvent('FULFILLMENT_COMPLETED', 'Fulfillment completed.', $userId);

            return $fulfillment->refresh();
        });
    }

    public function markDeliveryFailed(Fulfillment $fulfillment, string $reason, ?int $userId = null): Fulfillment
    {
        if (! $fulfillment->status->canMarkDeliveryFailed()) {
            throw new InvalidFulfillmentTransition('Delivery failure can only be recorded when out for delivery.');
        }

        $reason = trim($reason);
        if ($reason === '') {
            throw new \InvalidArgumentException('A failure reason is required.');
        }

        return DB::transaction(function () use ($fulfillment, $reason, $userId) {
            $fulfillment->update([
                'status' => FulfillmentStatus::DELIVERY_FAILED,
                'failure_reason' => $reason,
            ]);

            $fulfillment->logEvent('DELIVERY_FAILED', 'Delivery failed: '.$reason, $userId, [
                'failure_reason' => $reason,
            ]);

            return $fulfillment->refresh();
        });
    }

    public function retryDelivery(Fulfillment $fulfillment, ?int $userId = null): Fulfillment
    {
        if (! $fulfillment->status->canRetry()) {
            throw new InvalidFulfillmentTransition('Only failed deliveries can be retried.');
        }

        return DB::transaction(function () use ($fulfillment, $userId) {
            $fulfillment->update([
                'status' => FulfillmentStatus::READY,
                'failure_reason' => null,
            ]);

            $fulfillment->logEvent('RETRY_INITIATED', 'Delivery retry initiated.', $userId);

            return $fulfillment->refresh();
        });
    }

    public function cancel(Fulfillment $fulfillment, ?int $userId = null): Fulfillment
    {
        if (! $fulfillment->status->canCancel()) {
            throw new InvalidFulfillmentTransition('Fulfillment cannot be cancelled in its current state.');
        }

        return DB::transaction(function () use ($fulfillment, $userId) {
            $fulfillment->update([
                'status' => FulfillmentStatus::CANCELLED,
            ]);

            $fulfillment->logEvent('FULFILLMENT_CANCELLED', 'Fulfillment cancelled.', $userId);

            return $fulfillment->refresh();
        });
    }
}
