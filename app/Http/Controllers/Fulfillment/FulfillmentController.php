<?php

namespace App\Http\Controllers\Fulfillment;

use App\Http\Controllers\Controller;
use App\Models\Fulfillment\Fulfillment;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class FulfillmentController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request): View
    {
        $user = Auth::user();

        $fulfillments = Fulfillment::query()
            ->whereHas('order.request', function ($query) use ($user) {
                $query->where('customer_id', $user->customer->id);
            })
            ->with(['order', 'events'])
            ->latest()
            ->get();

        return view('fulfillment.index', compact('fulfillments'));
    }

    public function show(Fulfillment $fulfillment): View
    {
        $this->authorize('view', $fulfillment);

        $fulfillment->load(['order', 'events.user']);

        return view('fulfillment.show', compact('fulfillment'));
    }
}
