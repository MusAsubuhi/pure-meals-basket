<?php

namespace App\Http\Controllers\CustomerPortal;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class CustomerPaymentsController extends Controller
{
    public function index(): View
    {
        $customer = Auth::user()->customer;

        $payments = Payment::query()
            ->where('customer_id', $customer->id)
            ->with('order')
            ->latest()
            ->get()
            ->groupBy(fn (Payment $p) => $p->order?->reference ?? 'Other');

        return view('customer.payments', compact('payments', 'customer'));
    }
}
