<?php

namespace App\Services\Quotation;

use App\Enums\Quotation\QuotationStatus;
use App\Enums\Request\RequestStatus;
use App\Models\Quotation;
use App\Models\QuotationItem;
use App\Models\Request\Request;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

class QuotationOrchestrator
{
    public function __construct(
        protected QuotationCalculator $calculator,
    ) {}

    /**
     * Create a new draft quotation from a request.
     */
    public function create(Request $request, ?int $createdByUserId = null): Quotation
    {
        if (! $request->status->isCommercialPhase()) {
            throw new InvalidArgumentException('Request is not eligible for quotation.');
        }

        return DB::transaction(function () use ($request, $createdByUserId) {
            $quotation = new Quotation([
                'request_id' => $request->id,
                'reference' => Quotation::generateReference(),
                'status' => QuotationStatus::DRAFT,
                'created_by' => $createdByUserId,
            ]);
            $quotation->save();

            $quotation->logEvent('CREATED', 'Quotation draft created.', $createdByUserId);

            return $quotation;
        });
    }

    /**
     * Add an item to a draft quotation.
     */
    public function addItem(Quotation $quotation, array $itemData): QuotationItem
    {
        $this->ensureDraft($quotation);

        return DB::transaction(function () use ($quotation, $itemData) {
            $item = $quotation->items()->create([
                'request_item_id' => $itemData['request_item_id'] ?? null,
                'name' => $itemData['name'],
                'description' => $itemData['description'] ?? null,
                'quantity' => $itemData['quantity'] ?? 1,
                'unit' => $itemData['unit'] ?? null,
                'unit_price' => $itemData['unit_price'] ?? 0,
                'subtotal' => ($itemData['unit_price'] ?? 0) * ($itemData['quantity'] ?? 1),
                'metadata' => $itemData['metadata'] ?? null,
            ]);

            $this->recalculateTotals($quotation);

            $quotation->logEvent('ITEM_ADDED', "Item '{$item->name}' added.");

            return $item;
        });
    }

    /**
     * Update an item in a draft quotation.
     */
    public function updateItem(Quotation $quotation, QuotationItem $item, array $itemData): QuotationItem
    {
        $this->ensureDraft($quotation);

        if ($item->quotation_id !== $quotation->id) {
            throw new InvalidArgumentException('Item does not belong to this quotation.');
        }

        return DB::transaction(function () use ($quotation, $item, $itemData) {
            $item->update([
                'name' => $itemData['name'] ?? $item->name,
                'description' => $itemData['description'] ?? $item->description,
                'quantity' => $itemData['quantity'] ?? $item->quantity,
                'unit' => $itemData['unit'] ?? $item->unit,
                'unit_price' => $itemData['unit_price'] ?? $item->unit_price,
                'metadata' => $itemData['metadata'] ?? $item->metadata,
            ]);

            $item->update([
                'subtotal' => $item->unit_price * $item->quantity,
            ]);

            $this->recalculateTotals($quotation);

            $quotation->logEvent('ITEM_UPDATED', "Item '{$item->name}' updated.");

            return $item->refresh();
        });
    }

    /**
     * Remove an item from a draft quotation.
     */
    public function removeItem(Quotation $quotation, QuotationItem $item): void
    {
        $this->ensureDraft($quotation);

        if ($item->quotation_id !== $quotation->id) {
            throw new InvalidArgumentException('Item does not belong to this quotation.');
        }

        DB::transaction(function () use ($quotation, $item) {
            $name = $item->name;
            $item->delete();

            $this->recalculateTotals($quotation);

            $quotation->logEvent('ITEM_REMOVED', "Item '{$name}' removed.");
        });
    }

    /**
     * Apply a discount to a draft quotation.
     */
    public function applyDiscount(Quotation $quotation, float $discount): Quotation
    {
        $this->ensureDraft($quotation);

        return DB::transaction(function () use ($quotation, $discount) {
            $quotation->update(['discount' => $discount]);
            $this->recalculateTotals($quotation);

            $quotation->logEvent('DISCOUNT_APPLIED', "Discount of {$discount} applied.");

            return $quotation->refresh();
        });
    }

    /**
     * Send a quotation (DRAFT -> SENT).
     */
    public function send(Quotation $quotation, ?int $userId = null): Quotation
    {
        if (! $quotation->canBeSent()) {
            throw new RuntimeException('Quotation cannot be sent in its current state.');
        }

        if ($quotation->items()->count() === 0) {
            throw new InvalidArgumentException('Quotation must contain at least one item.');
        }

        return DB::transaction(function () use ($quotation, $userId) {
            $this->withdrawOtherSentQuotations($quotation->request);

            $quotation->update([
                'status' => QuotationStatus::SENT,
                'sent_at' => now(),
                'valid_until' => now()->addDays(7),
            ]);

            $quotation->logEvent('SENT', 'Quotation sent to customer.', $userId);

            return $quotation->refresh();
        });
    }

    /**
     * Withdraw a sent quotation (SENT -> WITHDRAWN).
     */
    public function withdraw(Quotation $quotation, ?int $userId = null): Quotation
    {
        if (! $quotation->canBeWithdrawn()) {
            throw new RuntimeException('Quotation cannot be withdrawn in its current state.');
        }

        return DB::transaction(function () use ($quotation, $userId) {
            $quotation->update([
                'status' => QuotationStatus::WITHDRAWN,
                'withdrawn_at' => now(),
            ]);

            $quotation->logEvent('WITHDRAWN', 'Quotation withdrawn by PMB.', $userId);

            return $quotation->refresh();
        });
    }

    /**
     * Decline a sent quotation (SENT -> DECLINED).
     */
    public function decline(Quotation $quotation, ?int $userId = null): Quotation
    {
        if (! $quotation->canBeDeclined()) {
            throw new RuntimeException('Quotation cannot be declined in its current state.');
        }

        return DB::transaction(function () use ($quotation, $userId) {
            $quotation->update([
                'status' => QuotationStatus::DECLINED,
                'declined_at' => now(),
            ]);

            $quotation->logEvent('DECLINED', 'Quotation declined by customer.', $userId);

            return $quotation->refresh();
        });
    }

    /**
     * Accept a sent quotation (SENT -> ACCEPTED).
     */
    public function accept(Quotation $quotation, ?int $userId = null): Quotation
    {
        if (! $quotation->canBeAccepted()) {
            throw new RuntimeException('Quotation cannot be accepted in its current state.');
        }

        if ($quotation->hasExpired()) {
            throw new RuntimeException('Quotation has expired.');
        }

        return DB::transaction(function () use ($quotation, $userId) {
            $quotation->update([
                'status' => QuotationStatus::ACCEPTED,
                'accepted_at' => now(),
            ]);

            $quotation->logEvent('ACCEPTED', 'Quotation accepted by customer.', $userId);

            $request = $quotation->request;
            $request->update(['status' => RequestStatus::READY_FOR_CHECKOUT]);
            $request->logEvent('QUOTATION_ACCEPTED', 'Customer accepted quotation '.$quotation->reference.'.', $userId);

            return $quotation->refresh();
        });
    }

    /**
     * Expire a sent quotation (SENT -> EXPIRED).
     */
    public function expire(Quotation $quotation, ?int $userId = null): Quotation
    {
        if (! $quotation->isSent() || ! $quotation->hasExpired()) {
            throw new RuntimeException('Quotation is not eligible for expiration.');
        }

        return DB::transaction(function () use ($quotation, $userId) {
            $quotation->update([
                'status' => QuotationStatus::EXPIRED,
                'expired_at' => now(),
            ]);

            $quotation->logEvent('EXPIRED', 'Quotation expired.', $userId);

            return $quotation->refresh();
        });
    }

    /**
     * Create a replacement quotation for a sent quotation.
     */
    public function createReplacement(Quotation $quotation, ?int $userId = null): Quotation
    {
        if (! $quotation->canBeReplaced()) {
            throw new RuntimeException('Quotation cannot be replaced in its current state.');
        }

        return DB::transaction(function () use ($quotation, $userId) {
            $quotation->update([
                'status' => QuotationStatus::WITHDRAWN,
                'withdrawn_at' => now(),
            ]);

            $quotation->logEvent('WITHDRAWN', 'Quotation withdrawn for replacement.', $userId);

            $replacement = $this->create($quotation->request, $userId);

            foreach ($quotation->items as $item) {
                $this->addItem($replacement, [
                    'request_item_id' => $item->request_item_id,
                    'name' => $item->name,
                    'description' => $item->description,
                    'quantity' => $item->quantity,
                    'unit' => $item->unit,
                    'unit_price' => $item->unit_price,
                    'metadata' => $item->metadata,
                ]);
            }

            $replacement->update([
                'discount' => $quotation->discount,
            ]);

            $this->recalculateTotals($replacement);

            $replacement->logEvent('REPLACEMENT_CREATED', 'Replacement quotation created for '.$quotation->reference.'.');

            return $replacement;
        });
    }

    /**
     * Recalculate quotation totals from items.
     */
    protected function recalculateTotals(Quotation $quotation): void
    {
        $subtotal = $quotation->items()->sum('subtotal');
        $discount = $quotation->discount ?? 0;
        $totals = $this->calculator->calculate($subtotal, $discount);

        $quotation->update([
            'subtotal' => $totals->subtotal,
            'discount' => $totals->discount,
            'total' => $totals->total,
        ]);
    }

    /**
     * Withdraw any other SENT quotations for the same request.
     */
    protected function withdrawOtherSentQuotations(Request $request): void
    {
        $request->quotations()
            ->where('status', QuotationStatus::SENT)
            ->each(function (Quotation $q) {
                if ($q->canBeWithdrawn()) {
                    $q->update([
                        'status' => QuotationStatus::WITHDRAWN,
                        'withdrawn_at' => now(),
                    ]);
                    $q->logEvent('WITHDRAWN', 'Quotation withdrawn because a replacement was sent.');
                }
            });
    }

    /**
     * Ensure quotation is in DRAFT status.
     */
    protected function ensureDraft(Quotation $quotation): void
    {
        if (! $quotation->canBeEdited()) {
            throw new RuntimeException('Quotation is no longer in draft status and cannot be modified.');
        }
    }
}
