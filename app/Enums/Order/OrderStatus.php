<?php

namespace App\Enums\Order;

enum OrderStatus: string
{
    case DRAFT = 'DRAFT';
    case PENDING_PAYMENT = 'PENDING_PAYMENT';
    case CONFIRMED = 'CONFIRMED';
    case PREPARING = 'PREPARING';
    case READY = 'READY';
    case OUT_FOR_DELIVERY = 'OUT_FOR_DELIVERY';
    case DELIVERED = 'DELIVERED';
    case COMPLETED = 'COMPLETED';
    case CANCELLED = 'CANCELLED';

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Draft',
            self::PENDING_PAYMENT => 'Pending Payment',
            self::CONFIRMED => 'Confirmed',
            self::PREPARING => 'Preparing',
            self::READY => 'Ready',
            self::OUT_FOR_DELIVERY => 'Out for Delivery',
            self::DELIVERED => 'Delivered',
            self::COMPLETED => 'Completed',
            self::CANCELLED => 'Cancelled',
        };
    }

    public function badgeColor(): string
    {
        return match ($this) {
            self::DRAFT => 'gray',
            self::PENDING_PAYMENT => 'warning',
            self::CONFIRMED => 'info',
            self::PREPARING => 'primary',
            self::READY => 'success',
            self::OUT_FOR_DELIVERY => 'info',
            self::DELIVERED => 'success',
            self::COMPLETED => 'success',
            self::CANCELLED => 'danger',
        };
    }

    public function isDraft(): bool
    {
        return $this === self::DRAFT;
    }

    public function isPendingPayment(): bool
    {
        return $this === self::PENDING_PAYMENT;
    }

    public function isConfirmed(): bool
    {
        return $this === self::CONFIRMED;
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

    public function isCompleted(): bool
    {
        return $this === self::COMPLETED;
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

    public function canBeCancelled(): bool
    {
        return in_array($this, [
            self::DRAFT,
            self::PENDING_PAYMENT,
        ], true);
    }

    public function canBeConfirmed(): bool
    {
        return $this === self::PENDING_PAYMENT;
    }

    public function canStartPreparing(): bool
    {
        return $this === self::CONFIRMED;
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

    public function canComplete(): bool
    {
        return in_array($this, [
            self::READY,
            self::OUT_FOR_DELIVERY,
            self::DELIVERED,
        ], true);
    }
}
