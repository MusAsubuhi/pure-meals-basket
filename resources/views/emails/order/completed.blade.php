@component('mail::message')
# Your order is complete

Hi {{ $request->customer->name }},

Thank you for choosing Pure Meals Basket. Your order **{{ $order->reference }}** is now complete.

| | |
| --- | --- |
| Event | {{ $request->event_date?->format('F j, Y') }} |
| Location | {{ $request->location ?? '—' }} |

We hope everything was simply delicious. If anything was not as you expected, just reply to this email and we will make it right.

[View your order]({{ route('orders.show', $order) }})

Simply delicious,
The PMB Team
@endcomponent
