@component('mail::message')
# Your order is ready

Hi {{ $request->customer->name }},

Your order **{{ $order->reference }}** is ready for collection or dispatch.

| | |
| --- | --- |
| Event | {{ $request->event_date?->format('F j, Y') }} |
| Location | {{ $request->location ?? '—' }} |

If this is a delivery order, it will be dispatched soon.

[Track your order]({{ route('orders.show', $order) }})

Simply delicious,  
The PMB Team
@endcomponent