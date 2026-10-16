<?php

namespace App\Enums;

enum DeliveryType: string
{
    case STANDARD = 'standard';
    case DOOR_TO_DOOR = 'door_to_door';
    case BEYOND_STAGE = 'beyond_stage';

    public function label(): string
    {
        return match ($this) {
            self::STANDARD => 'Standard',
            self::DOOR_TO_DOOR => 'Door-to-door',
            self::BEYOND_STAGE => 'Beyond stage',
        };
    }

    public function badgeColor(): string
    {
        return match ($this) {
            self::STANDARD => 'gray',
            self::DOOR_TO_DOOR => 'blue',
            self::BEYOND_STAGE => 'orange',
        };
    }
}