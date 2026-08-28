@extends('layouts.app')

@section('title', 'Payments - ' . $order->reference)

@section('content')
<div class="container" style="padding: 2rem 0;">
    <nav aria-label="breadcrumb" style="margin-bottom: 1.5rem;">
        <ol style="display: flex; gap: 0.5rem; list-style: none; padding: 0; margin: 0; font-size: 0.9rem;">
            <li><a href="{{ route('orders.show', $order) }}" style="color: #2563eb; text-decoration: none;">Order {{ $order->reference }}</a></li>
            <li style="color: #6b7280;">/</li>
            <li style="color: #374151;">Payments</li>
        </ol>
    </nav>

    <h1 style="font-size: 1.75rem; font-weight: 700; margin-bottom: 1.5rem;">Payments for {{ $order->reference }}</h1>

    @if(session('success'))
        <div style="background: #dcfce7; color: #166534; padding: 1rem; border-radius: 0.5rem; margin-bottom: 1.5rem;">
            {{ session('success') }}
        </div>
    @endif

    @if($order->balance_due > 0)
        <div style="background: #fef3c7; color: #92400e; padding: 1rem; border-radius: 0.5rem; margin-bottom: 1.5rem;">
            <strong>Outstanding balance:</strong> KSh {{ number_format($order->balance_due, 2) }}
        </div>
    @else
        <div style="background: #dcfce7; color: #166534; padding: 1rem; border-radius: 0.5rem; margin-bottom: 1.5rem;">
            <strong>Order fully paid.</strong>
        </div>
    @endif

    @if($order->canBeConfirmed() && $order->balance_due > 0)
        <div style="background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 0.5rem; padding: 1.5rem; margin-bottom: 2rem;">
            <h2 style="font-size: 1.25rem; font-weight: 600; margin-bottom: 1rem;">Make a Payment</h2>

            <div style="display: flex; flex-direction: column; gap: 1rem;">
                <form method="POST" action="{{ route('payments.mpesa', $order) }}" style="display: flex; flex-direction: column; gap: 0.75rem;">
                    @csrf
                    <div>
                        <label for="phone" style="display: block; font-weight: 600; margin-bottom: 0.25rem;">M-Pesa Phone Number</label>
                        <input type="text" id="phone" name="phone" value="{{ old('phone') }}" placeholder="0712345678" required
                            style="width: 100%; padding: 0.75rem; border: 1px solid #d1d5db; border-radius: 0.375rem; font-size: 1rem;">
                        @error('phone')
                            <span style="color: #dc2626; font-size: 0.875rem;">{{ $message }}</span>
                        @enderror
                    </div>
                    <button type="submit" style="background: #2563eb; color: white; padding: 0.75rem 1.5rem; border: none; border-radius: 0.375rem; font-size: 1rem; font-weight: 600; cursor: pointer; width: fit-content;">
                        Pay with M-Pesa
                    </button>
                </form>

                <form method="POST" action="{{ route('payments.cash', $order) }}" style="display: flex; flex-direction: column; gap: 0.75rem;">
                    @csrf
                    <button type="submit" style="background: #4b5563; color: white; padding: 0.75rem 1.5rem; border: none; border-radius: 0.375rem; font-size: 1rem; font-weight: 600; cursor: pointer; width: fit-content;">
                        Pay Cash (Staff Confirmation Required)
                    </button>
                </form>
            </div>
        </div>
    @endif

    <div style="background: white; border: 1px solid #e5e7eb; border-radius: 0.5rem; overflow: hidden;">
        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="background: #f9fafb; border-bottom: 1px solid #e5e7eb;">
                        <th style="text-align: left; padding: 0.75rem 1rem; font-weight: 600; font-size: 0.875rem;">Reference</th>
                        <th style="text-align: left; padding: 0.75rem 1rem; font-weight: 600; font-size: 0.875rem;">Method</th>
                        <th style="text-align: left; padding: 0.75rem 1rem; font-weight: 600; font-size: 0.875rem;">Provider</th>
                        <th style="text-align: right; padding: 0.75rem 1rem; font-weight: 600; font-size: 0.875rem;">Amount</th>
                        <th style="text-align: center; padding: 0.75rem 1rem; font-weight: 600; font-size: 0.875rem;">Status</th>
                        <th style="text-align: left; padding: 0.75rem 1rem; font-weight: 600; font-size: 0.875rem;">Paid At</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($payments as $payment)
                        <tr style="border-bottom: 1px solid #f3f4f6;">
                            <td style="padding: 0.75rem 1rem; font-size: 0.875rem;">{{ $payment->reference }}</td>
                            <td style="padding: 0.75rem 1rem; font-size: 0.875rem;">{{ $payment->method->label() }}</td>
                            <td style="padding: 0.75rem 1rem; font-size: 0.875rem;">{{ $payment->provider->label() }}</td>
                            <td style="padding: 0.75rem 1rem; font-size: 0.875rem; text-align: right;">KSh {{ number_format($payment->amount, 2) }}</td>
                            <td style="padding: 0.75rem 1rem; font-size: 0.875rem; text-align: center;">
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
                            </td>
                            <td style="padding: 0.75rem 1rem; font-size: 0.875rem;">{{ $payment->paid_at?->format('Y-m-d H:i') ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" style="padding: 2rem; text-align: center; color: #6b7280;">No payments recorded yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
