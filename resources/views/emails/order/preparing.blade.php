@component('mail::message')
# Your order is being prepared

Hi {{ $request->customer->name }},

Great news — your order **{{ $order->reference }}** is now being prepared in our kitchen.

| | |
| --- | --- |
| Event | {{ $request->event_date?->format('F j, Y') }} |
| Location | {{ $request->location ?? '—' }} |

We'll notify you as soon as it's ready for collection or dispatch.

[Track your order]({{ route('orders.show', $order) }})

Simply delicious,  
The PMB Team
@endcomponent