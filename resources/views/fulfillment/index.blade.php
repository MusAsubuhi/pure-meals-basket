@extends('layouts.app')

@section('title', 'My Fulfillments')

@section('content')
<div class="container" style="padding: 2rem 0;">
    <h1 style="font-size: 1.75rem; font-weight: 700; margin-bottom: 1.5rem;">My Fulfillments</h1>

    <div style="background: white; border: 1px solid #e5e7eb; border-radius: 0.5rem; overflow: hidden;">
        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="background: #f9fafb; border-bottom: 1px solid #e5e7eb;">
                        <th style="text-align: left; padding: 0.75rem 1rem; font-weight: 600; font-size: 0.875rem;">Order</th>
                        <th style="text-align: left; padding: 0.75rem 1rem; font-weight: 600; font-size: 0.875rem;">Method</th>
                        <th style="text-align: center; padding: 0.75rem 1rem; font-weight: 600; font-size: 0.875rem;">Status</th>
                        <th style="text-align: left; padding: 0.75rem 1rem; font-weight: 600; font-size: 0.875rem;">Scheduled</th>
                        <th style="text-align: left; padding: 0.75rem 1rem; font-weight: 600; font-size: 0.875rem;">Updated</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($fulfillments as $fulfillment)
                        <tr style="border-bottom: 1px solid #f3f4f6;">
                            <td style="padding: 0.75rem 1rem; font-size: 0.875rem;">
                                <a href="{{ route('orders.show', $fulfillment->order) }}" style="color: #2563eb; text-decoration: none;">
                                    {{ $fulfillment->order->reference }}
                                </a>
                            </td>
                            <td style="padding: 0.75rem 1rem; font-size: 0.875rem;">{{ $fulfillment->method->label() }}</td>
                            <td style="padding: 0.75rem 1rem; font-size: 0.875rem; text-align: center;">
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
                            </td>
                            <td style="padding: 0.75rem 1rem; font-size: 0.875rem;">{{ $fulfillment->scheduled_at?->format('Y-m-d H:i') ?? '-' }}</td>
                            <td style="padding: 0.75rem 1rem; font-size: 0.875rem;">{{ $fulfillment->updated_at->format('Y-m-d H:i') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" style="padding: 2rem; text-align: center; color: #6b7280;">No fulfillments found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
