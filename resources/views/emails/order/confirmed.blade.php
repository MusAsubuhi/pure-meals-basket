@component('mail::message')
# Your order is confirmed

Hi {{ $request->customer->name }},

Your order **{{ $order->reference }}** has been confirmed. We're getting everything ready for your event.

| | |
| --- | --- |
| Event | {{ $request->event_date?->format('F j, Y') }} |
| Time | {{ $request->event_time?->format('g:i A') ?? '—' }} |
| Location | {{ $request->location ?? '—' }} |
| Total | KSh {{ number_format($order->total, 2) }} |

We'll keep you updated as your order moves through preparation and delivery.

[Track your order]({{ route('orders.show', $order) }})

Simply delicious,  
The PMB Team
@endcomponent