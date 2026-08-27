@extends('layouts.app')

@section('title', "Request {$request->reference}")

@section('content')
<div class="container">
    <div class="mb-3">
        <a href="{{ route('requests.index') }}" class="btn btn-secondary">
            &larr; Back to Requests
        </a>
    </div>

    <div class="row">
        <div class="col-md-8">
            <h1>{{ $request->reference }}</h1>

            <div class="card mb-3">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Status:</strong>
                                <span class="badge bg-{{ $request->status->badgeColor() }}">
                                    {{ $request->status->label() }}
                                </span>
                            </p>
                            <p><strong>Event Date:</strong> {{ $request->event_date?->format('F j, Y') ?? 'Not specified' }}</p>
                            <p><strong>Event Time:</strong> {{ $request->event_time?->format('g:i A') ?? 'Not specified' }}</p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Location:</strong> {{ $request->location ?? 'Not specified' }}</p>
                            <p><strong>Submitted:</strong> {{ $request->submitted_at?->format('M j, Y g:i A') ?? 'Not submitted' }}</p>
                        </div>
                    </div>
                    @if($request->notes)
                        <hr>
                        <p><strong>Notes:</strong></p>
                        <p>{{ $request->notes }}</p>
                    @endif
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-body">
                    <h5>Items</h5>
                    @forelse($request->items as $item)
                        <div class="border-bottom py-2">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <strong>{{ $item->name }}</strong>
                                    <br>
                                    <small class="text-muted">
                                        {{ $item->quantity }} {{ $item->unit ?? 'unit' }}
                                        @if($item->pricing_type)
                                            | {{ $item->pricing_type->label() }}
                                        @endif
                                    </small>
                                </div>
                                <div class="text-end">
                                    @if($item->subtotal)
                                        <strong>KSh {{ number_format($item->subtotal, 2) }}</strong>
                                    @elseif($item->isQuotationRequired())
                                        <span class="badge bg-warning">Quotation Required</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @empty
                        <p class="text-muted">No items in this request</p>
                    @endforelse
                </div>
            </div>

            @if($request->clarifications->count() > 0)
                <div class="card mb-3">
                    <div class="card-body">
                        <h5>Clarifications</h5>
                        @foreach($request->clarifications as $clarification)
                            <div class="alert alert-{{ $clarification->hasBeenAnswered() ? 'success' : 'info' }}">
                                <p class="mb-1"><strong>Q:</strong> {{ $clarification->question }}</p>
                                @if($clarification->hasBeenAnswered())
                                    <p class="mb-1"><strong>A:</strong> {{ $clarification->response }}</p>
                                    <small class="text-muted">Answered: {{ $clarification->responded_at->format('M j, Y g:i A') }}</small>
                                @else
                                    <form method="POST" action="{{ route('requests.respond', $clarification) }}">
                                        @csrf
                                        <div class="mb-2">
                                            <textarea class="form-control" name="response" rows="2"
                                                      placeholder="Your response..." required></textarea>
                                        </div>
                                        <button type="submit" class="btn btn-sm btn-primary">Respond</button>
                                    </form>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>

        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <h5>Request Timeline</h5>
                    @foreach($request->events as $event)
                        <div class="mb-3">
                            <small class="text-muted">{{ $event->created_at->format('M j, g:i A') }}</small>
                            <p class="mb-0">{{ $event->description }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
