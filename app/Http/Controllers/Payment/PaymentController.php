<?php

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Payment;
use App\Services\Payment\PaymentOrchestrator;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request as HttpRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class PaymentController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        protected PaymentOrchestrator $orchestrator,
    ) {}

    public function index(Order $order): View
    {
        $this->authorize('view', $order);

        $payments = $order->payments()->latest()->get();

        return view('payment.index', compact('order', 'payments'));
    }

    public function initiateMpesa(HttpRequest $httpRequest, Order $order): RedirectResponse
    {
        $this->authorize('initiatePayment', $order);

        $validated = $httpRequest->validate([
            'phone' => 'required|string|max:20',
            'amount' => 'nullable|numeric|min:0.01',
        ]);

        $amount = $validated['amount'] ?? null;

        $payment = $this->orchestrator->initiateMpesa($order, $validated['phone'], Auth::id(), $amount);

        return redirect()->route('payments.show', ['order' => $order->id, 'payment' => $payment->id])
            ->with('success', 'M-Pesa payment request sent. Please check your phone and enter your PIN.');
    }

    public function recordCash(Order $order): RedirectResponse
    {
        $this->authorize('initiatePayment', $order);

        $payment = $this->orchestrator->recordCash($order, Auth::id());

        return redirect()->route('payments.show', ['order' => $order->id, 'payment' => $payment->id])
            ->with('success', 'Cash payment recorded. Awaiting staff confirmation.');
    }

    public function confirmCash(Order $order, Payment $payment): RedirectResponse
    {
        $this->authorize('confirmCash', $payment);

        $this->orchestrator->confirmCash($payment, Auth::id());

        return redirect()->route('payments.show', ['order' => $order->id, 'payment' => $payment->id])
            ->with('success', 'Cash payment confirmed successfully.');
    }

    public function retry(Order $order, Payment $payment): RedirectResponse
    {
        $this->authorize('initiatePayment', $order);

        if ($payment->order_id !== $order->id) {
            abort(404);
        }

        $newPayment = $this->orchestrator->retryMpesa($payment, Auth::id());

        return redirect()->route('payments.show', ['order' => $order->id, 'payment' => $newPayment->id])
            ->with('success', 'Retrying payment. Please check your phone for the M-Pesa prompt.');
    }

    public function show(Order $order, Payment $payment): View
    {
        $this->authorize('view', $payment);

        return view('payment.show', compact('order', 'payment'));
    }

    public function status(Order $order, Payment $payment): View
    {
        $this->authorize('view', $payment);

        $this->orchestrator->verifyPayment($payment);

        return view('payment.show', compact('order', 'payment'));
    }
}
