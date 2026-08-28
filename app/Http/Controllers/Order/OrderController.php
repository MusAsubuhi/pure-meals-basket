<?php

namespace App\Http\Controllers\Order;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\Order\OrderOrchestrator;
use Illuminate\Http\Request as HttpRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

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
}
