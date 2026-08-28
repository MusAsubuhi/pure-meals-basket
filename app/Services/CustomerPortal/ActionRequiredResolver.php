<?php

namespace App\Services\CustomerPortal;

use App\Enums\Order\OrderStatus;
use App\Enums\Quotation\QuotationStatus;
use App\Models\Customer;
use App\Models\Request\Request;

/**
 * Answers the dashboard's most important question:
 * "What do I need to do right now?"
 *
 * Walks the customer's requests and surfaces the single highest-priority
 * pending actions (clarification to answer, quotation to review, payment to
 * make, or an order in progress) in a customer-facing shape. The header uses
 * this to show an action badge; the dashboard renders the full list.
 */
class ActionRequiredResolver
{
    /**
     * @return array<int, array{
     *      priority: int, kind: string, tone: string, icon: string,
     *      title: string, detail: string, cta: string, url: string,
     *      needsAction: bool, request_id: string
     * }>
     */
    public function resolve(Customer $customer): array
    {
        $actions = [];

        $requests = $customer->requests()
            ->whereNull('deleted_at')
            ->with(['clarifications', 'quotations', 'orders', 'items'])
            ->get();

        foreach ($requests as $request) {
            $actions = array_merge($actions, $this->actionsForRequest($request));
        }

        usort($actions, fn ($a, $b) => $a['priority'] <=> $b['priority']);

        return $actions;
    }

    /** Count of items genuinely waiting on the customer. */
    public function countNeedingAction(Customer $customer): int
    {
        return collect($this->resolve($customer))
            ->where('needsAction', true)
            ->count();
    }

    protected function actionsForRequest(Request $request): array
    {
        $actions = [];

        // 1. Clarifications awaiting an answer
        foreach ($request->clarifications as $clarification) {
            if (! $clarification->hasBeenAnswered()) {
                $actions[] = [
                    'priority' => 1,
                    'kind' => 'clarification',
                    'tone' => 'orange',
                    'icon' => '✉️',
                    'title' => 'PMB needs more information',
                    'detail' => 'Answer a question about '.$this->requestLabel($request).' to keep things moving.',
                    'cta' => 'Respond',
                    'url' => route('requests.show', $request).'#clarifications',
                    'needsAction' => true,
                    'request_id' => $request->id,
                ];
            }
        }

        // 2. Quotation awaiting a decision (sent, not expired)
        foreach ($request->quotations as $quotation) {
            if ($quotation->status === QuotationStatus::SENT && ! $quotation->hasExpired()) {
                $actions[] = [
                    'priority' => 2,
                    'kind' => 'quotation',
                    'tone' => 'gold',
                    'icon' => '📋',
                    'title' => 'Your quotation is ready',
                    'detail' => $this->requestLabel($request),
                    'cta' => 'Review quotation',
                    'url' => route('quotations.show', $quotation),
                    'needsAction' => true,
                    'request_id' => $request->id,
                ];
            }
        }

        // 3. Outstanding payment
        foreach ($request->orders as $order) {
            if ($order->status === OrderStatus::PENDING_PAYMENT && $order->balance_due > 0) {
                $actions[] = [
                    'priority' => 3,
                    'kind' => 'payment',
                    'tone' => 'danger',
                    'icon' => '💳',
                    'title' => 'Payment required',
                    'detail' => $order->reference.' · KSh '.number_format($order->balance_due, 2).' outstanding.',
                    'cta' => 'Pay now',
                    'url' => route('payments.index', $order),
                    'needsAction' => true,
                    'request_id' => $request->id,
                ];
            }
        }

        // 4. Active order to track (informational)
        if (empty($actions)) {
            $activeOrder = $request->orders->first(fn ($o) => ! $o->isTerminal());
            if ($activeOrder) {
                $actions[] = [
                    'priority' => 4,
                    'kind' => 'track',
                    'tone' => 'blue',
                    'icon' => '🚚',
                    'title' => $activeOrder->reference.' in progress',
                    'detail' => $this->requestLabel($request),
                    'cta' => 'Track order',
                    'url' => route('orders.show', $activeOrder),
                    'needsAction' => false,
                    'request_id' => $request->id,
                ];
            }
        }

        return $actions;
    }

    protected function requestLabel(Request $request): string
    {
        if ($request->items->isNotEmpty()) {
            return $request->items->first()->name;
        }

        return $request->reference;
    }
}
