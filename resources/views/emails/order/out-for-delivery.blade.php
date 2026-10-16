@component('mail::message')
# Your order is on the way

Hi {{ $request->customer->name }},

Your order **{{ $order->reference }}** is now out for delivery.

| | |
| --- | --- |
| Event | {{ $request->event_date?->format('F j, Y') }} |
| Location | {{ $request->location ?? '—' }} |

We'll notify you as soon as it's delivered.

[Track your order]({{ route('orders.show', $order) }})

Simply delicious,  
The PMB Team
@endcomponent