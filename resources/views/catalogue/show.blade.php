@extends('catalogue.layout')

@section('title', $product->name)

@section('content')
<div class="container" x-data="quoter()">
    <a class="back" href="{{ route('catalogue.category', $product->category) }}">← {{ $product->category->name }}</a>
    <h1>{{ $product->name }}</h1>
    @if ($product->short_description)<p class="subtitle">{{ $product->short_description }}</p>@endif

    @if ($product->description)
        <div style="background:#fff;border-radius:.75rem;padding:1.25rem;margin-bottom:1.5rem;">
            {!! nl2br(e($product->description)) !!}
        </div>
    @endif

    {{-- Configuration & live estimate --}}
    <form style="max-width:480px;" @submit.prevent>
        @if (! $product->pricing_type->usesQuantity())
            {{-- fixed & custom pricing need no quantity input --}}
        @else
            <label for="quantity">Quantity{{ $product->unit ? ' (' . $product->unit . ')' : '' }}</label>
            <input type="number" id="quantity" x-model.number="quantity"
                   min="{{ $product->minimum_quantity ?? 0 }}"
                   max="{{ $product->maximum_quantity ?? '' }}"
                   step="0.5">
        @endif

        @foreach($product->options as $option)
            <label for="opt-{{ $option->id }}">{{ $option->name }}@if($option->is_required) * @endif</label>
            <select id="opt-{{ $option->id }}" name="options[{{ $option->id }}]"
                    x-on:change="requote()" x-model.number="selected[{{$option->id}}]">
                <option value="">— choose —</option>
                @foreach($option->values as $value)
                    <option value="{{ $value->id }}">
                        {{ $value->name }}@if((float)$value->price_modifier > 0) (+KSh {{ number_format($value->price_modifier,0) }})@endif
                    </option>
                @endforeach
            </select>
        @endforeach
    </form>

    @if ($product->pricing_type === \App\Enums\PricingType::CUSTOM)
        <p class="badge" style="margin-bottom:1rem;">Custom item — PMB will prepare a quotation.</p>
    @endif

    {{-- Sticky live quote box --}}
    <div class="quote-box" x-show="$store.ui">
        <template x-if="error">
            <p class="unavailable" x-text="error"></p>
        </template>

        <template x-if="requiresQuote && !error">
            <div>
                <strong>Quotation required</strong><br>
                <small>PMB will review your request and confirm a price.</small>
            </div>
        </template>

        <template x-if="total !== null && !error && !requiresQuote">
            <p>Estimated total: <span class="total-line" x-text="'KSh ' + Number(total).toLocaleString(undefined,{minimumFractionDigits:2})"></span></p>
        </template>
    </div>
</div>

<script>
function quoter() {
    return {
        quantity: @if($product->minimum_quantity) {{ $product->minimum_quantity }} @else null @endif,
        selected: {},
        total: null,
        requiresQuote: false,
        error: null,

        init() {
            this.$watch('quantity', () => this.requote());
            this.requote();
        },

        async requote() {
            const payload = {
                type: 'product',
                id: {{ $product->id }},
                quantity: this.quantity,
                option_value_ids: Object.values(this.selected).filter(v => v),
            };

            try {
                const res = await fetch('{{ route('catalogue.quote') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    },
                    body: JSON.stringify(payload),
                });
                const data = await res.json();

                if (!res.ok) {
                    this.error = data.message || 'Could not calculate a price.';
                    this.total = null;
                    this.requiresQuote = false;
                    return;
                }

                this.error = null;
                this.total = data.total;
                this.requiresQuote = data.requires_pmb_quote;
            } catch (e) {
                this.error = 'Could not reach the pricing service.';
            }
        }
    }
}
</script>
@endsection
