@extends('layouts.app')

@section('title', 'Payment ' . $payment->reference)

@section('content')
<div class="container" style="padding: 2rem 0;">
    <nav aria-label="breadcrumb" style="margin-bottom: 1.5rem;">
        <ol style="display: flex; gap: 0.5rem; list-style: none; padding: 0; margin: 0; font-size: 0.9rem;">
            <li><a href="{{ route('orders.show', $order) }}" style="color: #2563eb; text-decoration: none;">Order {{ $order->reference }}</a></li>
            <li style="color: #6b7280;">/</li>
            <li><a href="{{ route('payments.index', $order) }}" style="color: #2563eb; text-decoration: none;">Payments</a></li>
            <li style="color: #6b7280;">/</li>
            <li style="color: #374151;">{{ $payment->reference }}</li>
        </ol>
    </nav>

    <h1 style="font-size: 1.75rem; font-weight: 700; margin-bottom: 1.5rem;">Payment {{ $payment->reference }}</h1>

    @if(session('success'))
        <div style="background: #dcfce7; color: #166534; padding: 1rem; border-radius: 0.5rem; margin-bottom: 1.5rem;">
            {{ session('success') }}
        </div>
    @endif

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">
        <div style="background: white; border: 1px solid #e5e7eb; border-radius: 0.5rem; padding: 1.5rem;">
            <h2 style="font-size: 1.125rem; font-weight: 600; margin-bottom: 1rem;">Payment Details</h2>
            <dl style="display: grid; grid-template-columns: auto 1fr; gap: 0.5rem 1rem;">
                <dt style="font-weight: 600; color: #4b5563;">Reference</dt>
                <dd>{{ $payment->reference }}</dd>

                <dt style="font-weight: 600; color: #4b5563;">Order</dt>
                <dd><a href="{{ route('orders.show', $order) }}" style="color: #2563eb; text-decoration: none;">{{ $order->reference }}</a></dd>

                <dt style="font-weight: 600; color: #4b5563;">Amount</dt>
                <dd>KSh {{ number_format($payment->amount, 2) }}</dd>

                <dt style="font-weight: 600; color: #4b5563;">Method</dt>
                <dd>{{ $payment->method->label() }}</dd>

                <dt style="font-weight: 600; color: #4b5563;">Provider</dt>
                <dd>{{ $payment->provider->label() }}</dd>

                <dt style="font-weight: 600; color: #4b5563;">Status</dt>
                <dd>
                    @php
                        $badgeColors = [
                            'PENDING' => 'background: #f3f4f6; color: #374151;',
                            'PROCESSING' => 'background: #fef3c7; color: #92400e;',
                            'SUCCESS' => 'background: #dcfce7; color: #166534;',
                            'FAILED' => 'background: #fee2e2; color: #991b1b;',
                            'CANCELLED' => 'background: #fee2e2; color: #991b1b;',
                            'REVERSED' => 'background: #ffedd5; color: #9a3412;',
                        ];
                    @endphp
                    <span style="{{ $badgeColors[$payment->status->value] ?? 'background: #f3f4f6;' }} padding: 0.25rem 0.5rem; border-radius: 9999px; font-size: 0.75rem; font-weight: 500;">
                        {{ $payment->status->label() }}
                    </span>
                </dd>

                <dt style="font-weight: 600; color: #4b5563;">Paid At</dt>
                <dd>{{ $payment->paid_at?->format('Y-m-d H:i') ?? '-' }}</dd>

                <dt style="font-weight: 600; color: #4b5563;">Provider Ref</dt>
                <dd>{{ $payment->provider_reference ?? '-' }}</dd>

                <dt style="font-weight: 600; color: #4b5563;">Checkout ID</dt>
                <dd>{{ $payment->checkout_request_id ?? '-' }}</dd>
            </dl>
        </div>

        <div style="background: white; border: 1px solid #e5e7eb; border-radius: 0.5rem; padding: 1.5rem;">
            <h2 style="font-size: 1.125rem; font-weight: 600; margin-bottom: 1rem;">Order Summary</h2>
            <dl style="display: grid; grid-template-columns: auto 1fr; gap: 0.5rem 1rem;">
                <dt style="font-weight: 600; color: #4b5563;">Order Total</dt>
                <dd>KSh {{ number_format($order->total, 2) }}</dd>

                <dt style="font-weight: 600; color: #4b5563;">Payment Required</dt>
                <dd>KSh {{ number_format($order->payment_required, 2) }}</dd>

                <dt style="font-weight: 600; color: #4b5563;">Amount Paid</dt>
                <dd>KSh {{ number_format($order->amount_paid, 2) }}</dd>

                <dt style="font-weight: 600; color: #4b5563;">Balance Due</dt>
                <dd>KSh {{ number_format($order->balance_due, 2) }}</dd>
            </dl>
        </div>
    </div>

    <div style="background: white; border: 1px solid #e5e7eb; border-radius: 0.5rem; overflow: hidden;">
        <div style="padding: 1rem 1.5rem; border-bottom: 1px solid #e5e7eb;">
            <h2 style="font-size: 1.125rem; font-weight: 600;">Payment Events</h2>
        </div>
        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="background: #f9fafb; border-bottom: 1px solid #e5e7eb;">
                        <th style="text-align: left; padding: 0.75rem 1rem; font-weight: 600; font-size: 0.875rem;">Event</th>
                        <th style="text-align: left; padding: 0.75rem 1rem; font-weight: 600; font-size: 0.875rem;">Description</th>
                        <th style="text-align: left; padding: 0.75rem 1rem; font-weight: 600; font-size: 0.875rem;">When</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($payment->events as $event)
                        <tr style="border-bottom: 1px solid #f3f4f6;">
                            <td style="padding: 0.75rem 1rem; font-size: 0.875rem;">
                                @php
                                    $eventColors = [
                                        'INITIATED' => 'background: #f3f4f6; color: #374151;',
                                        'PROCESSING' => 'background: #fef3c7; color: #92400e;',
                                        'STK_PUSH_SENT' => 'background: #dbeafe; color: #1e40af;',
                                        'SUCCESS' => 'background: #dcfce7; color: #166534;',
                                        'FAILED' => 'background: #fee2e2; color: #991b1b;',
                                        'CANCELLED' => 'background: #fee2e2; color: #991b1b;',
                                        'REVERSED' => 'background: #ffedd5; color: #9a3412;',
                                    ];
                                @endphp
                                <span style="{{ $eventColors[$event->event_type] ?? 'background: #f3f4f6;' }} padding: 0.25rem 0.5rem; border-radius: 9999px; font-size: 0.75rem; font-weight: 500;">
                                    {{ $event->event_type }}
                                </span>
                            </td>
                            <td style="padding: 0.75rem 1rem; font-size: 0.875rem;">{{ $event->data['description'] ?? '-' }}</td>
                            <td style="padding: 0.75rem 1rem; font-size: 0.875rem;">{{ $event->created_at->format('Y-m-d H:i') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" style="padding: 2rem; text-align: center; color: #6b7280;">No events recorded.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
