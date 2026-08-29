<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Request\Request as RequestModel;
use App\Models\Request\RequestClarification;
use App\Services\Request\RequestOrchestrator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request as HttpRequest;
use Illuminate\View\View;

class RequestController extends Controller
{
    public function __construct(
        protected RequestOrchestrator $orchestrator,
    ) {}

    /**
     * Customer's request history.
     */
    public function index(): View
    {
        $customer = auth()->user()->customer;
        $requests = $customer->requests()
            ->orderByDesc('created_at')
            ->get();

        return view('request.index', compact('requests'));
    }

    /**
     * View request details.
     */
    public function show(RequestModel $request): View
    {
        $this->authorizeCustomerAccess($request);

        return view('request.show', compact('request'));
    }

    /**
     * Finalize cart into draft request.
     */
    public function checkout(): View
    {
        $customer = auth()->user()->customer;
        $draft = $this->orchestrator->createDraftForCustomer($customer);

        return view('request.checkout', compact('draft'));
    }

    /**
     * Convert draft to SUBMITTED.
     */
    public function submit(HttpRequest $httpRequest): RedirectResponse
    {
        $validated = $httpRequest->validate([
            'request_id' => 'required|exists:requests,id',
            'event_date' => 'required|date',
            'event_time' => 'nullable|date_format:H:i',
            'location' => 'required|string|max:255',
            'notes' => 'nullable|string',
        ]);

        $request = RequestModel::findOrFail($validated['request_id']);
        $this->authorizeCustomerAccess($request);

        if (! $request->status->customerEditable()) {
            return back()->with('error', 'Cannot submit a request in this status.');
        }

        $request->update([
            'event_date' => $validated['event_date'],
            'event_time' => $validated['event_time'],
            'location' => $validated['location'],
            'notes' => $validated['notes'],
            'submitted_at' => now(),
        ]);

        $this->orchestrator->hydrateRequestFromCart($request);
        $this->orchestrator->submitRequest($request);
        $this->orchestrator->autoApproveIfPossible($request->refresh());

        return redirect()->route('requests.show', $request)
            ->with('success', 'Request submitted successfully.');
    }

    /**
     * Answer a clarification (customer).
     */
    public function respond(HttpRequest $httpRequest, RequestClarification $clarification): RedirectResponse
    {
        $this->authorizeCustomerAccess($clarification->request);

        if ($clarification->hasBeenAnswered()) {
            return back()->with('error', 'This clarification has already been answered.');
        }

        $validated = $httpRequest->validate([
            'response' => 'required|string',
        ]);

        $this->orchestrator->respondToClarification(
            $clarification,
            auth()->id(),
            $validated['response']
        );

        return back()->with('success', 'Response submitted.');
    }

    /**
     * Ensure the customer can access this request.
     */
    protected function authorizeCustomerAccess(RequestModel $request): void
    {
        if ($request->customer_id !== auth()->user()->customer->id) {
            abort(403);
        }
    }
}
