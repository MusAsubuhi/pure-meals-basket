<?php

namespace App\Enums\Payment;

enum PaymentStatus: string
{
    case PENDING = 'PENDING';
    case PROCESSING = 'PROCESSING';
    case SUCCESS = 'SUCCESS';
    case FAILED = 'FAILED';
    case CANCELLED = 'CANCELLED';
    case REVERSED = 'REVERSED';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Pending',
            self::PROCESSING => 'Processing',
            self::SUCCESS => 'Success',
            self::FAILED => 'Failed',
            self::CANCELLED => 'Cancelled',
            self::REVERSED => 'Reversed',
        };
    }

    public function badgeColor(): string
    {
        return match ($this) {
            self::PENDING => 'gray',
            self::PROCESSING => 'warning',
            self::SUCCESS => 'success',
            self::FAILED => 'danger',
            self::CANCELLED => 'danger',
            self::REVERSED => 'orange',
        };
    }

    public function isTerminal(): bool
    {
        return in_array($this, [
            self::SUCCESS,
            self::FAILED,
            self::CANCELLED,
            self::REVERSED,
        ], true);
    }

    public function isSuccess(): bool
    {
        return $this === self::SUCCESS;
    }

    public function isProcessing(): bool
    {
        return $this === self::PROCESSING;
    }
}
