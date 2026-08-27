@extends('layouts.app')

@section('title', 'Checkout')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-md-8">
            <h1>Checkout</h1>

            <form method="POST" action="{{ route('request.submit') }}">
                @csrf

                <input type="hidden" name="request_id" value="{{ $draft->id }}">

                <div class="card mb-3">
                    <div class="card-body">
                        <h5>Event Details</h5>
                        <div class="mb-3">
                            <label class="form-label">Event Date *</label>
                            <input type="date" class="form-control" name="event_date" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Event Time</label>
                            <input type="time" class="form-control" name="event_time">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Location *</label>
                            <input type="text" class="form-control" name="location" required
                                   placeholder="Delivery address or venue">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Special Notes</label>
                            <textarea class="form-control" name="notes" rows="3"
                                      placeholder="Any special requests or dietary requirements..."></textarea>
                        </div>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary">Submit Request</button>
                <a href="{{ route('request.cart') }}" class="btn btn-secondary">Back to Cart</a>
            </form>
        </div>

        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <h5>Request Reference</h5>
                    <p class="lead">{{ $draft->reference }}</p>
                    <small class="text-muted">Draft request - not yet submitted</small>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
