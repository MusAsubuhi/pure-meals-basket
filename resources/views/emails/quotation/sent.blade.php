@component('mail::message')
# Your quotation is ready

Hi {{ $request->customer->name }},

We've prepared a quotation for your request **{{ $request->reference }}**.

| Item | Amount |
| --- | --- |
| Subtotal | KSh {{ number_format($quotation->subtotal, 2) }} |
@if((float)$quotation->discount > 0)
| Discount | − KSh {{ number_format($quotation->discount, 2) }} @endif
| **Total** | **KSh {{ number_format($quotation->total, 2) }}** |

@if($quotation->valid_until)
This quotation is valid until **{{ $quotation->valid_until->format('F j, Y') }}**.
@endif

You can review and accept it at any time:

[Review quotation]({{ route('quotations.show', $quotation) }})

If anything needs adjusting, just let us know and we'll prepare a revised quotation.

Simply delicious,  
The PMB Team
@endcomponent