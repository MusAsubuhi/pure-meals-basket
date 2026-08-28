@extends('layouts.app')

@section('title', 'Fulfillment ' . $fulfillment->order->reference)

@section('content')
<div class="container" style="padding: 2rem 0;">
    <nav aria-label="breadcrumb" style="margin-bottom: 1.5rem;">
        <ol style="display: flex; gap: 0.5rem; list-style: none; padding: 0; margin: 0; font-size: 0.9rem;">
            <li><a href="{{ route('fulfillments.index') }}" style="color: #2563eb; text-decoration: none;">Fulfillments</a></li>
            <li style="color: #6b7280;">/</li>
            <li style="color: #374151;">{{ $fulfillment->order->reference }}</li>
        </ol>
    </nav>

    <h1 style="font-size: 1.75rem; font-weight: 700; margin-bottom: 1.5rem;">Fulfillment {{ $fulfillment->order->reference }}</h1>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">
        <div style="background: white; border: 1px solid #e5e7eb; border-radius: 0.5rem; padding: 1.5rem;">
            <h2 style="font-size: 1.125rem; font-weight: 600; margin-bottom: 1rem;">Fulfillment Details</h2>
            <dl style="display: grid; grid-template-columns: auto 1fr; gap: 0.5rem 1rem;">
                <dt style="font-weight: 600; color: #4b5563;">Order</dt>
                <dd><a href="{{ route('orders.show', $fulfillment->order) }}" style="color: #2563eb; text-decoration: none;">{{ $fulfillment->order->reference }}</a></dd>

                <dt style="font-weight: 600; color: #4b5563;">Method</dt>
                <dd>{{ $fulfillment->method->label() }}</dd>

                <dt style="font-weight: 600; color: #4b5563;">Status</dt>
                <dd>
                    @php
                        $badgeColors = [
                            'PENDING' => 'background: #f3f4f6; color: #374151;',
                            'PREPARING' => 'background: #fef3c7; color: #92400e;',
                            'READY' => 'background: #dcfce7; color: #166534;',
                            'OUT_FOR_DELIVERY' => 'background: #dbeafe; color: #1e40af;',
                            'DELIVERED' => 'background: #dcfce7; color: #166534;',
                            'COLLECTED' => 'background: #dcfce7; color: #166534;',
                            'SERVICE_IN_PROGRESS' => 'background: #dbeafe; color: #1e40af;',
                            'COMPLETED' => 'background: #dcfce7; color: #166534;',
                            'DELIVERY_FAILED' => 'background: #fee2e2; color: #991b1b;',
                            'CANCELLED' => 'background: #fee2e2; color: #991b1b;',
                        ];
                    @endphp
                    <span style="{{ $badgeColors[$fulfillment->status->value] ?? 'background: #f3f4f6;' }} padding: 0.25rem 0.5rem; border-radius: 9999px; font-size: 0.75rem; font-weight: 500;">
                        {{ $fulfillment->status->label() }}
                    </span>
                </dd>

                <dt style="font-weight: 600; color: #4b5563;">Scheduled</dt>
                <dd>{{ $fulfillment->scheduled_at?->format('Y-m-d H:i') ?? '-' }}</dd>

                @if($fulfillment->delivery_address)
                    <dt style="font-weight: 600; color: #4b5563;">Delivery Address</dt>
                    <dd>{{ $fulfillment->delivery_address }}</dd>
                @endif

                @if($fulfillment->recipient_name)
                    <dt style="font-weight: 600; color: #4b5563;">Recipient</dt>
                    <dd>{{ $fulfillment->recipient_name }}</dd>
                @endif
            </dl>
        </div>

        <div style="background: white; border: 1px solid #e5e7eb; border-radius: 0.5rem; padding: 1.5rem;">
            <h2 style="font-size: 1.125rem; font-weight: 600; margin-bottom: 1rem;">Timeline</h2>
            <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                @foreach($fulfillment->events as $event)
                    <div style="display: flex; gap: 1rem; align-items: flex-start;">
                        <div style="width: 8px; height: 8px; background: #2563eb; border-radius: 50%; margin-top: 0.5rem; flex-shrink: 0;"></div>
                        <div>
                            <div style="font-weight: 600; font-size: 0.875rem;">{{ $event->event_type }}</div>
                            <div style="color: #6b7280; font-size: 0.875rem;">{{ $event->description ?? '-' }}</div>
                            <div style="color: #9ca3af; font-size: 0.75rem;">{{ $event->created_at->format('Y-m-d H:i') }}</div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
@endsection
