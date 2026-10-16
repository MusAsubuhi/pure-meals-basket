<?php

namespace App\Http\Controllers\Order;

use App\Enums\DeliveryType;
use App\Enums\Order\FulfillmentMethod;
use App\Models\DeliveryZone;
use App\Models\Order;
use App\Services\Order\OrderOrchestrator;
use Illuminate\Http\Request as HttpRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function __construct(
        protected OrderOrchestrator $orchestrator,
    ) {}

    public function index(): View
    {
        $orders = Order::whereHas('request.customer.user', function ($query) {
            $query->where('id', Auth::id());
        })->latest()->get();

        return view('order.index', compact('orders'));
    }

    public function show(Order $order): View
    {
        $this->authorize('view', $order);

        return view('order.show', compact('order'));
    }

    public function cancel(Order $order): RedirectResponse
    {
        $this->authorize('cancel', $order);

        $this->orchestrator->cancel($order, Auth::id());

        return back()->with('success', 'Order cancelled successfully.');
    }

    /**
     * Show the fulfillment setup form (method + delivery zone + type).
     */
    public function fulfillForm(Order $order): View
    {
        $this->authorize('view', $order);

        $zones = DeliveryZone::active()->ordered()->get();

        return view('order.fulfill', compact('order', 'zones'));
    }

    /**
     * Persist the customer's fulfillment choices.
     */
    public function setFulfillment(HttpRequest $request, Order $order): RedirectResponse
    {
        $this->authorize('view', $order);

        $validated = $request->validate([
            'fulfillment_method' => 'required|in:'.implode(',', FulfillmentMethod::values()),
            'delivery_zone_id' => 'nullable|exists:delivery_zones,id',
            'delivery_type' => 'nullable|in:'.implode(',', DeliveryType::values()),
            'delivery_fee' => 'nullable|numeric|min:0',
        ]);

        $method = FulfillmentMethod::from($validated['fulfillment_method']);
        $zone = isset($validated['delivery_zone_id'])
            ? DeliveryZone::find($validated['delivery_zone_id'])
            : null;
        $type = isset($validated['delivery_type'])
            ? DeliveryType::from($validated['delivery_type'])
            : null;

        if ($zone !== null && $type !== null) {
            $fee = $zone->chargeFor($type, $order->subtotal);
        } else {
            $fee = (float) ($validated['delivery_fee'] ?? 0);
        }

        $this->orchestrator->setFulfillment(
            $order,
            $method->value,
            $fee,
            Auth::id(),
            $zone,
            $type,
        );

        return redirect()->route('orders.show', $order)
            ->with('success', 'Fulfillment set up successfully.');
    }

    /**
     * Show the delivery-zone / delivery-type selection form for an
     * already-confirmed order.
     */
    public function deliveryForm(Order $order): View
    {
        $this->authorize('view', $order);

        $zones = DeliveryZone::active()->ordered()->get();

        return view('order.delivery', compact('order', 'zones'));
    }

    /**
     * Persist the customer's delivery-zone / delivery-type choice.
     */
    public function setDelivery(HttpRequest $request, Order $order): RedirectResponse
    {
        $this->authorize('view', $order);

        $validated = $request->validate([
            'delivery_zone_id' => 'required|exists:delivery_zones,id',
            'delivery_type' => 'required|in:'.implode(',', DeliveryType::values()),
        ]);

        $type = DeliveryType::from($validated['delivery_type']);

        $this->orchestrator->setDeliveryType($order, $type, Auth::id());

        return redirect()->route('orders.show', $order)
            ->with('success', 'Delivery options updated.');
    }
}