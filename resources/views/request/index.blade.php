@extends('layouts.app')

@section('title', 'My Requests')

@section('content')
<div class="container">
    <h1>My Requests</h1>
    <p class="text-muted mb-4">Track your service requests and their status</p>

    @forelse($requests as $request)
        <div class="card mb-3">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h5>{{ $request->reference }}</h5>
                        <p class="text-muted mb-1">
                            Event: {{ $request->event_date ? $request->event_date->format('F j, Y') : 'Not specified' }}
                            @if($request->event_time)
                                at {{ $request->event_time->format('g:i A') }}
                            @endif
                        </p>
                        <p class="text-muted mb-1">
                            Location: {{ $request->location ?? 'Not specified' }}
                        </p>
                        <small class="text-muted">
                            Created: {{ $request->created_at->format('M j, Y g:i A') }}
                        </small>
                    </div>
                    <div class="text-end">
                        <span class="badge bg-{{ $request->status->badgeColor() }}">
                            {{ $request->status->label() }}
                        </span>
                        <br>
                        <a href="{{ route('requests.show', $request) }}" class="btn btn-sm btn-outline-primary mt-2">
                            View Details
                        </a>
                    </div>
                </div>
            </div>
        </div>
    @empty
        <div class="alert alert-info">
            You haven't submitted any requests yet.
            <a href="{{ route('catalogue.index') }}" class="alert-link">Browse our catalogue</a>
        </div>
    @endforelse
</div>
@endsection
