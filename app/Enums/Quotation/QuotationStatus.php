<?php

namespace App\Enums\Quotation;

enum QuotationStatus: string
{
    case DRAFT       = 'DRAFT';
    case SENT        = 'SENT';
    case ACCEPTED    = 'ACCEPTED';
    case DECLINED    = 'DECLINED';
    case WITHDRAWN   = 'WITHDRAWN';
    case EXPIRED     = 'EXPIRED';

    public function label(): string
    {
        return match ($this) {
            self::DRAFT     => 'Draft',
            self::SENT      => 'Sent',
            self::ACCEPTED  => 'Accepted',
            self::DECLINED  => 'Declined',
            self::WITHDRAWN => 'Withdrawn',
            self::EXPIRED   => 'Expired',
        };
    }

    public function badgeColor(): string
    {
        return match ($this) {
            self::DRAFT     => 'gray',
            self::SENT      => 'blue',
            self::ACCEPTED  => 'green',
            self::DECLINED  => 'red',
            self::WITHDRAWN => 'orange',
            self::EXPIRED   => 'gray',
        };
    }

    public function isDraft(): bool
    {
        return $this === self::DRAFT;
    }

    public function isSent(): bool
    {
        return $this === self::SENT;
    }

    public function isAccepted(): bool
    {
        return $this === self::ACCEPTED;
    }

    public function isDeclined(): bool
    {
        return $this === self::DECLINED;
    }

    public function isWithdrawn(): bool
    {
        return $this === self::WITHDRAWN;
    }

    public function isExpired(): bool
    {
        return $this === self::EXPIRED;
    }

    public function isTerminal(): bool
    {
        return in_array($this, [
            self::ACCEPTED,
            self::DECLINED,
            self::WITHDRAWN,
            self::EXPIRED,
        ], true);
    }

    public function canBeEdited(): bool
    {
        return $this === self::DRAFT;
    }

    public function canBeSent(): bool
    {
        return $this === self::DRAFT;
    }

    public function canBeAccepted(): bool
    {
        return $this === self::SENT;
    }

    public function canBeDeclined(): bool
    {
        return $this === self::SENT;
    }

    public function canBeWithdrawn(): bool
    {
        return $this === self::SENT;
    }

    public function canBeReplaced(): bool
    {
        return $this === self::SENT;
    }
}
