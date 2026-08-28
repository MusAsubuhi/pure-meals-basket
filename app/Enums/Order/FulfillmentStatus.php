<?php

namespace App\Enums\Order;

enum FulfillmentStatus: string
{
    case PENDING = 'PENDING';
    case PREPARING = 'PREPARING';
    case READY = 'READY';
    case OUT_FOR_DELIVERY = 'OUT_FOR_DELIVERY';
    case DELIVERED = 'DELIVERED';
    case COLLECTED = 'COLLECTED';
    case SERVICE_IN_PROGRESS = 'SERVICE_IN_PROGRESS';
    case COMPLETED = 'COMPLETED';
    case DELIVERY_FAILED = 'DELIVERY_FAILED';
    case CANCELLED = 'CANCELLED';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Pending',
            self::PREPARING => 'Preparing',
            self::READY => 'Ready',
            self::OUT_FOR_DELIVERY => 'Out for Delivery',
            self::DELIVERED => 'Delivered',
            self::COLLECTED => 'Collected',
            self::SERVICE_IN_PROGRESS => 'Service In Progress',
            self::COMPLETED => 'Completed',
            self::DELIVERY_FAILED => 'Delivery Failed',
            self::CANCELLED => 'Cancelled',
        };
    }

    public function badgeColor(): string
    {
        return match ($this) {
            self::PENDING => 'gray',
            self::PREPARING => 'warning',
            self::READY => 'success',
            self::OUT_FOR_DELIVERY => 'info',
            self::DELIVERED => 'success',
            self::COLLECTED => 'success',
            self::SERVICE_IN_PROGRESS => 'primary',
            self::COMPLETED => 'success',
            self::DELIVERY_FAILED => 'danger',
            self::CANCELLED => 'danger',
        };
    }

    public function isPending(): bool
    {
        return $this === self::PENDING;
    }

    public function isPreparing(): bool
    {
        return $this === self::PREPARING;
    }

    public function isReady(): bool
    {
        return $this === self::READY;
    }

    public function isOutForDelivery(): bool
    {
        return $this === self::OUT_FOR_DELIVERY;
    }

    public function isDelivered(): bool
    {
        return $this === self::DELIVERED;
    }

    public function isCollected(): bool
    {
        return $this === self::COLLECTED;
    }

    public function isServiceInProgress(): bool
    {
        return $this === self::SERVICE_IN_PROGRESS;
    }

    public function isCompleted(): bool
    {
        return $this === self::COMPLETED;
    }

    public function isDeliveryFailed(): bool
    {
        return $this === self::DELIVERY_FAILED;
    }

    public function isCancelled(): bool
    {
        return $this === self::CANCELLED;
    }

    public function isTerminal(): bool
    {
        return in_array($this, [
            self::COMPLETED,
            self::CANCELLED,
        ], true);
    }

    public function canStartPreparing(): bool
    {
        return $this === self::PENDING;
    }

    public function canMarkReady(): bool
    {
        return $this === self::PREPARING;
    }

    public function canDispatch(): bool
    {
        return $this === self::READY;
    }

    public function canMarkDelivered(): bool
    {
        return $this === self::OUT_FOR_DELIVERY;
    }

    public function canMarkCollected(): bool
    {
        return $this === self::READY;
    }

    public function canStartService(): bool
    {
        return $this === self::READY;
    }

    public function canComplete(): bool
    {
        return in_array($this, [
            self::DELIVERED,
            self::COLLECTED,
            self::SERVICE_IN_PROGRESS,
        ], true);
    }

    public function canMarkDeliveryFailed(): bool
    {
        return $this === self::OUT_FOR_DELIVERY;
    }

    public function canRetry(): bool
    {
        return $this === self::DELIVERY_FAILED;
    }

    public function canCancel(): bool
    {
        return in_array($this, [
            self::PENDING,
            self::PREPARING,
            self::READY,
            self::DELIVERY_FAILED,
        ], true);
    }
}
