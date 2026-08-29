<?php

namespace App\Enums\Request;

enum RequestStatus: string
{
    case DRAFT = 'DRAFT';
    case SUBMITTED = 'SUBMITTED';
    case UNDER_REVIEW = 'UNDER_REVIEW';
    case NEEDS_INFORMATION = 'NEEDS_INFORMATION';
    case QUOTATION_REQUIRED = 'QUOTATION_REQUIRED';
    case READY_FOR_CHECKOUT = 'READY_FOR_CHECKOUT';
    case DECLINED = 'DECLINED';
    case CANCELLED = 'CANCELLED';

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Draft',
            self::SUBMITTED => 'Submitted',
            self::UNDER_REVIEW => 'Under Review',
            self::NEEDS_INFORMATION => 'Needs Information',
            self::QUOTATION_REQUIRED => 'Quotation Required',
            self::READY_FOR_CHECKOUT => 'Ready for Checkout',
            self::DECLINED => 'Declined',
            self::CANCELLED => 'Cancelled',
        };
    }

    public function badgeColor(): string
    {
        return match ($this) {
            self::DRAFT => 'gray',
            self::SUBMITTED => 'blue',
            self::UNDER_REVIEW => 'yellow',
            self::NEEDS_INFORMATION => 'orange',
            self::QUOTATION_REQUIRED => 'purple',
            self::READY_FOR_CHECKOUT => 'green',
            self::DECLINED => 'red',
            self::CANCELLED => 'red',
        };
    }

    /**
     * Can the customer still edit the request?
     */
    public function customerEditable(): bool
    {
        return $this === self::DRAFT;
    }

    /**
      * Has PMB started the commercial workflow?
      */
    public function isCommercialPhase(): bool
    {
        return in_array($this, [
            self::SUBMITTED,
            self::UNDER_REVIEW,
            self::NEEDS_INFORMATION,
            self::QUOTATION_REQUIRED,
            self::READY_FOR_CHECKOUT,
        ], true);
    }
}
