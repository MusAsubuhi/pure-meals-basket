<?php

namespace App\Enums;

enum CatalogStatus: string
{
    case DRAFT = 'draft';
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
    case ARCHIVED = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Draft',
            self::ACTIVE => 'Active',
            self::INACTIVE => 'Inactive',
            self::ARCHIVED => 'Archived',
        };
    }

    /**
     * Items may only be requested from the storefront while ACTIVE.
     * Archived/inactive/draft items remain reachable internally so that
     * historical requests keep their snapshots intact.
     */
    public function isRequestable(): bool
    {
        return $this === self::ACTIVE;
    }

    /**
     * Status values that indicate the item is visible to PMB staff
     * as part of live operations (not draft, not archived).
     */
    public static function operational(): array
    {
        return [
            self::ACTIVE,
            self::INACTIVE,
        ];
    }
}
