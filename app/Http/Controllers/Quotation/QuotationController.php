<?php

namespace App\Http\Controllers\Quotation;

use App\Http\Controllers\Controller;
use App\Models\Quotation;
use App\Services\Quotation\QuotationOrchestrator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request as HttpRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class QuotationController extends Controller
{
    public function __construct(
        protected QuotationOrchestrator $orchestrator,
    ) {}

    public function show(Quotation $quotation): View
    {
        $this->authorize('view', $quotation);

        return view('quotation.show', compact('quotation'));
    }

    /**
     * The customer's quotation history.
     */
    public function index(): View
    {
        $customer = Auth::user()->customer;

        $quotations = Quotation::query()
            ->whereHas('request', fn ($q) => $q->where('customer_id', $customer->id))
            ->with('request')
            ->latest()
            ->get();

        return view('quotation.index', compact('quotations'));
    }

    public function accept(Quotation $quotation): RedirectResponse
    {
        $this->authorize('accept', $quotation);

        $this->orchestrator->accept($quotation, Auth::id());

        return back()->with('success', 'Quotation accepted successfully.');
    }

    public function decline(Quotation $quotation): RedirectResponse
    {
        $this->authorize('decline', $quotation);

        $this->orchestrator->decline($quotation, Auth::id());

        return back()->with('success', 'Quotation declined.');
    }

    public function requestChanges(HttpRequest $httpRequest, Quotation $quotation): RedirectResponse
    {
        $this->authorize('requestChanges', $quotation);

        $validated = $httpRequest->validate([
            'change_reason' => 'required|string|max:2000',
        ]);

        $replacement = $this->orchestrator->createReplacement($quotation, Auth::id());

        $replacement->logEvent('REPLACEMENT_CREATED', 'Customer requested changes: '.$validated['change_reason'], Auth::id());

        return redirect()->route('requests.show', $quotation->request)
            ->with('success', 'Change request submitted. PMB will prepare a revised quotation.');
    }
}
