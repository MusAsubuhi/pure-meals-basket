<?php

namespace App\Services\Request;

use App\Enums\Request\RequestItemPricingStatus;
use App\Enums\Request\RequestStatus;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Request\Request as RequestModel;
use App\Models\Request\RequestClarification;
use App\Models\Request\RequestItem;
use App\Models\Service;
use App\Services\Pricing\ProductPricingService;
use App\Services\Pricing\QuoteResult;
use App\Services\Quotation\QuotationOrchestrator;
use App\Services\Order\OrderOrchestrator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use RuntimeException;

class RequestOrchestrator
{
    public function __construct(
        protected ProductPricingService $pricingService,
        protected QuotationOrchestrator $quotationOrchestrator,
        protected OrderOrchestrator $orderOrchestrator,
    ) {}

    protected string $cartKey = 'pmb_request_cart';

    /**
     * Get the current customer's session cart.
     * Items store item_type + item_id + quantity + option_ids only —
     * prices are always recalculated server-side.
     */
    public function cart(): array
    {
        return Session::get($this->cartKey, []);
    }

    /**
     * Add a catalogue item to the session cart.
     *
     * @param  string  $itemType  "product" | "service"
     * @param  int  $itemId  PK of product or service
     * @param  float  $quantity  customer-entered quantity
     * @param  array  $optionIds  selected ProductOptionValue IDs
     */
    public function addToCart(string $itemType, int $itemId, float $quantity, array $optionIds = []): void
    {
        $cart = $this->cart();
        $key = "{$itemType}:{$itemId}";

        $cart[$key] = [
            'item_type' => $itemType,
            'item_id' => $itemId,
            'quantity' => $quantity,
            'option_ids' => $optionIds,
        ];

        Session::put($this->cartKey, $cart);
    }

    /**
     * Remove an item from the session cart by its composite key.
     */
    public function removeFromCart(string $itemKey): void
    {
        $cart = $this->cart();
        unset($cart[$itemKey]);
        Session::put($this->cartKey, $cart);
    }

    /**
     * Empty the session cart entirely.
     */
    public function clearCart(): void
    {
        Session::forget($this->cartKey);
    }

    /**
     * Create a new request draft for a customer — empty, no items yet.
     */
    public function createDraftForCustomer(Customer $customer): RequestModel
    {
        return DB::transaction(function () use ($customer) {
            $request = $customer->requests()->create([
                'reference' => RequestModel::generateReference(),
                'status' => RequestStatus::DRAFT,
            ]);

            $request->logEvent(
                'REQUEST_CREATED',
                "Request {$request->reference} created in draft.",
            );

            return $request;
        });
    }

    /**
     * Hydrate a request from the session cart.
     */
    public function hydrateRequestFromCart(RequestModel $request): RequestModel
    {
        $cart = $this->cart();

        if (empty($cart)) {
            throw new RuntimeException('Cart is empty.');
        }

        if (! $this->isCustomerEditable($request->status)) {
            throw new RuntimeException('Cannot modify a request that is no longer in draft status.');
        }

        DB::transaction(function () use ($request, $cart) {
            foreach ($cart as $item) {
                $this->createItemFromCart($request, $item);
            }

            $this->recalculateRequestState($request);
        });

        $this->clearCart();

        return $request->refresh();
    }

    /**
     * Submit: DRAFT → SUBMITTED (customer action).
     */
    public function submitRequest(RequestModel $request): void
    {
        $this->transition(
            $request,
            RequestStatus::SUBMITTED,
            'REQUEST_SUBMITTED',
            'Request submitted by customer.',
            $request->customer_id,
        );
    }

    /**
     * Auto-approve a request if all items have calculable prices.
     * Creates an order directly and transitions to READY_FOR_CHECKOUT.
     */
    public function autoApproveIfPossible(RequestModel $request): void
    {
        if (! $request->isAutoApprovable()) {
            return;
        }

        DB::transaction(function () use ($request) {
            $this->orderOrchestrator->createFromRequest($request);

            $this->transition(
                $request,
                RequestStatus::READY_FOR_CHECKOUT,
                'AUTO_APPROVED',
                'Request auto-approved — all items have fixed prices.',
                $request->customer_id,
            );
        });
    }

    /**
     * Start PMB review: SUBMITTED → UNDER_REVIEW.
     */
    public function startReview(RequestModel $request, int $staffId): void
    {
        $this->transition(
            $request,
            RequestStatus::UNDER_REVIEW,
            'REVIEW_STARTED',
            'PMB staff has started reviewing the request.',
            $staffId,
        );
    }

    /**
     * Request information from customer: UNDER_REVIEW → NEEDS_INFORMATION.
     */
    public function requestInformation(RequestModel $request, int $staffId, string $question): RequestClarification
    {
        return DB::transaction(function () use ($request, $staffId, $question) {
            $clarification = $request->clarifications()->create([
                'asked_by_user_id' => $staffId,
                'question' => $question,
            ]);

            $this->transition(
                $request,
                RequestStatus::NEEDS_INFORMATION,
                'INFORMATION_REQUESTED',
                'PMB requested additional information.',
                $staffId,
            );

            return $clarification;
        });
    }

    /**
     * Customer responds to clarification: NEEDS_INFORMATION → UNDER_REVIEW.
     */
    public function respondToClarification(RequestClarification $clarification, int $customerId, string $answer): void
    {
        DB::transaction(function () use ($clarification, $customerId, $answer) {
            $clarification->update([
                'response' => $answer,
                'responded_by_user_id' => $customerId,
                'responded_at' => now(),
            ]);

            $request = $clarification->request;
            $this->transition(
                $request,
                RequestStatus::UNDER_REVIEW,
                'INFORMATION_PROVIDED',
                'Customer provided requested information.',
                $customerId,
            );
        });
    }

    /**
     * Mark request as ready for checkout: UNDER_REVIEW → READY_FOR_CHECKOUT.
     */
    public function markReadyForCheckout(RequestModel $request, int $staffId): void
    {
        $this->transition(
            $request,
            RequestStatus::READY_FOR_CHECKOUT,
            'READY_FOR_CHECKOUT',
            'Request approved and ready for customer checkout.',
            $staffId,
        );
    }

    /**
     * Transition a request to QUOTATION_REQUIRED status.
     */
    public function transitionRequestToQuotationRequired(RequestModel $request, ?int $staffId = null): void
    {
        $this->transition(
            $request,
            RequestStatus::QUOTATION_REQUIRED,
            'QUOTATION_CREATED',
            'Quotation created for request.',
            $staffId,
        );
    }

    /**
     * Create a quotation from request items and transition to QUOTATION_REQUIRED.
     */
    public function createQuotationFromRequest(RequestModel $request, ?int $staffId = null): \App\Models\Quotation
    {
        if (! $request->status->isCommercialPhase()) {
            throw new \InvalidArgumentException('Request is not eligible for quotation.');
        }

        return DB::transaction(function () use ($request, $staffId) {
            $quotation = $this->quotationOrchestrator->create($request, $staffId);

            foreach ($request->items as $item) {
                $this->quotationOrchestrator->addItem($quotation, [
                    'request_item_id' => $item->id,
                    'name' => $item->name,
                    'description' => null,
                    'quantity' => $item->quantity,
                    'unit' => $item->unit,
                    'unit_price' => $item->unit_price,
                    'metadata' => $item->pricing_breakdown,
                ]);
            }

            $this->transition(
                $request,
                RequestStatus::QUOTATION_REQUIRED,
                'QUOTATION_CREATED',
                'Quotation created for request.',
                $staffId,
            );

            return $quotation;
        });
    }

    /**
     * Decline a request: any status → DECLINED.
     */
    public function decline(RequestModel $request, int $staffId, string $reason): void
    {
        $this->transition(
            $request,
            RequestStatus::DECLINED,
            'REQUEST_DECLINED',
            "Request declined: {$reason}",
            $staffId,
        );
    }

    /**
     * Cancel a request: DRAFT/SUBMITTED → CANCELLED.
     */
    public function cancel(RequestModel $request, int $userId, string $reason): void
    {
        if (! $request->status->customerEditable() && $request->status !== RequestStatus::SUBMITTED) {
            throw new RuntimeException('Cannot cancel a request in this status.');
        }

        $this->transition(
            $request,
            RequestStatus::CANCELLED,
            'REQUEST_CANCELLED',
            "Request cancelled: {$reason}",
            $userId,
        );
    }

    /**
     * Create a request item from cart data.
     */
    protected function createItemFromCart(RequestModel $request, array $cartItem): RequestItem
    {
        $item = match ($cartItem['item_type']) {
            'product' => Product::findOrFail($cartItem['item_id']),
            'service' => Service::findOrFail($cartItem['item_id']),
            default => throw new RuntimeException("Invalid item type: {$cartItem['item_type']}"),
        };

        $quote = $this->pricingService->quote(
            $item,
            $cartItem['quantity'],
            $cartItem['option_ids'] ?? []
        );

        return $request->items()->create([
            'item_type' => $cartItem['item_type'],
            'product_id' => $cartItem['item_type'] === 'product' ? $cartItem['item_id'] : null,
            'service_id' => $cartItem['item_type'] === 'service' ? $cartItem['item_id'] : null,
            'name' => $item->name,
            'quantity' => $cartItem['quantity'],
            'unit' => $item->unit,
            'options' => $cartItem['option_ids'] ?? [],
            'pricing_type' => $quote->pricing_type,
            'pricing_status' => $quote->requires_pmb_quote
                ? RequestItemPricingStatus::QUOTATION_REQUIRED
                : RequestItemPricingStatus::CALCULATED,
            'unit_price' => $quote->unit_price,
            'subtotal' => $quote->subtotal,
            'pricing_breakdown' => $quote->breakdown,
        ]);
    }

    /**
     * Recalculate request totals and determine derived state.
     */
    protected function recalculateRequestState(RequestModel $request): void
    {
        $this->calculateRequestTotals($request);

        // If any item requires quotation, set request to QUOTATION_REQUIRED
        $hasQuotationRequired = $request->items->contains(fn ($item) => $item->isQuotationRequired());

        if ($hasQuotationRequired && $request->status === RequestStatus::DRAFT) {
            $request->update(['status' => RequestStatus::QUOTATION_REQUIRED]);
            $request->logEvent('QUOTATION_REQUIRED', 'Request contains items requiring PMB quotation.');
        }
    }

    /**
     * Calculate totals for all items in a request.
     * Only recalculates for requests still in DRAFT status.
     */
    public function calculateRequestTotals(RequestModel $request): void
    {
        if (! $request->status->customerEditable()) {
            return;
        }

        foreach ($request->items as $item) {
            if ($item->isCalculated() && $item->pricing_status === RequestItemPricingStatus::CALCULATED) {
                $catalogItem = $item->product ?? $item->service;
                if ($catalogItem) {
                    $quote = $this->pricingService->quote(
                        $catalogItem,
                        $item->quantity,
                        $item->options ?? []
                    );
                    $item->update([
                        'unit_price' => $quote->unit_price,
                        'subtotal' => $quote->subtotal,
                        'pricing_breakdown' => $quote->breakdown,
                    ]);
                }
            }
        }
    }

    /**
     * Quote a single request item.
     */
    public function quoteItem(RequestItem $item): QuoteResult
    {
        $catalogItem = $item->product ?? $item->service;

        if (! $catalogItem) {
            throw new RuntimeException('Request item has no associated product or service.');
        }

        return $this->pricingService->quote(
            $catalogItem,
            $item->quantity,
            $item->options ?? []
        );
    }

    /**
     * Check if a request status allows customer editing.
     */
    protected function isCustomerEditable(RequestStatus $status): bool
    {
        return $status->customerEditable();
    }

    /**
     * Perform a status transition with event logging.
     */
    protected function transition(
        RequestModel $request,
        RequestStatus $newStatus,
        string $eventType,
        string $description,
        ?int $userId = null,
        array $metadata = []
    ): void {
        $oldStatus = $request->status;

        DB::transaction(function () use ($request, $newStatus, $eventType, $description, $userId, $metadata, $oldStatus) {
            $request->update(['status' => $newStatus]);

            $request->logEvent($eventType, $description, $userId, array_merge($metadata, [
                'old_status' => $oldStatus->value,
                'new_status' => $newStatus->value,
            ]));
        });
    }
}
