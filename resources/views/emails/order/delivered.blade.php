@component('mail::message')
# Your order has been delivered

Hi {{ $request->customer->name }},

Your order **{{ $order->reference }}** has been delivered. We hope you enjoyed it!

| | |
| --- | --- |
| Event | {{ $request->event_date?->format('F j, Y') }} |
| Location | {{ $request->location ?? '—' }} |

Thank you for choosing Pure Meals Basket. We'd love to hear how everything went.

[Track your order]({{ route('orders.show', $order) }})

Simply delicious,  
The PMB Team
@endcomponent