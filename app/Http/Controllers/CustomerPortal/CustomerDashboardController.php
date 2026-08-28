<?php

namespace App\Http\Controllers\CustomerPortal;

use App\Http\Controllers\Controller;
use App\Services\CustomerPortal\ActionRequiredResolver;
use App\Services\CustomerPortal\UnifiedTimeline;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class CustomerDashboardController extends Controller
{
    public function __construct(
        protected ActionRequiredResolver $resolver,
        protected UnifiedTimeline $timeline,
    ) {}

    public function index(): View
    {
        $customer = Auth::user()->customer;

        $requests = $customer->requests()
            ->whereNull('deleted_at')
            ->with(['items', 'quotations', 'orders.fulfillment', 'orders.payments'])
            ->latest()
            ->get();

        $allActions = $this->resolver->resolve($customer);
        $actionNeeded = collect($allActions)->where('needsAction', true)->values();
        $tracking = collect($allActions)->where('needsAction', false)->values();

        // Active orders (via request), most recent first.
        $orders = \App\Models\Order::query()
            ->whereHas('request', fn ($q) => $q->where('customer_id', $customer->id))
            ->with(['request', 'fulfillment'])
            ->latest()
            ->get();

        // Recent activity across this customer's requests (unified timeline).
        $activity = [];
        foreach ($requests as $request) {
            foreach ($this->timeline->timeline($request) as $ev) {
                $activity[] = $ev;
            }
        }
        usort($activity, fn ($a, $b) => $b['at'] <=> $a['at']);
        $activity = array_slice($activity, 0, 8);

        return view('customer.dashboard', compact(
            'customer',
            'requests',
            'orders',
            'allActions',
            'actionNeeded',
            'tracking',
            'activity'
        ));
    }
}
