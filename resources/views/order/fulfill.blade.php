@extends('layouts.customer')

@section('title', 'Fulfillment — ' . $order->reference)

@section('content')
<div class="pmb-page-title">
    <div class="pmb-row">
        <div>
            <h1 class="pmb-h1">How would you like PMB to handle your order?</h1>
            <p>{{ $order->reference }}</p>
        </div>
    </div>
</div>

<div class="pmb-grid pmb-grid--main">
    <div class="pmb-card">
        <h2 class="pmb-card__title">Fulfillment method</h2>
        <p style="color:var(--ink-muted);font-size:.9rem;margin:0 0 1rem;">Choose how PMB will provide your order.</p>

        <form method="POST" action="{{ route('orders.fulfill.set', $order) }}" class="pmb-form">
            @csrf

            <div class="pmb-field">
                <label class="pmb-label" for="fulfillment_method">Method *</label>
                <select class="pmb-input" id="fulfillment_method" name="fulfillment_method" required>
                    <option value="">— Select method —</option>
                    @foreach(FulfillmentMethod::cases() as $method)
                        <option value="{{ $method->value }}">{{ $method->label() }}</option>
                    @endforeach
                </select>
                @error('fulfillment_method') <span class="pmb-error">{{ $message }}</span> @endif
            </div>

            <div id="delivery-options" style="display:none;">
                <div class="pmb-field">
                    <label class="pmb-label" for="delivery_zone_id">Delivery area *</label>
                    <select class="pmb-input" id="delivery_zone_id" name="delivery_zone_id">
                        <option value="">— Select area —</option>
                        @foreach($zones as $zone)
                            <option value="{{ $zone->id }}">{{ $zone->name }}
                                @if((float)$zone->base_charge > 0) (from KSh {{ number_format($zone->base_charge, 2) }}) @endif
                            </option>
                        @endforeach
                    </select>
                    @error('delivery_zone_id') <span class="pmb-error">{{ $message }}</span> @endif
                </div>

                <div class="pmb-field">
                    <label class="pmb-label" for="delivery_type">Delivery type *</label>
                    <select class="pmb-input" id="delivery_type" name="delivery_type">
                        <option value="">— Select type —</option>
                        @foreach(DeliveryType::cases() as $type)
                            <option value="{{ $type->value }}">{{ $type->label() }}</option>
                        @endforeach
                    </select>
                    <p class="pmb-hint">Standard = zone base charge only. Door-to-door adds the door-to-door surcharge. Beyond stage adds the beyond-stage surcharge.</p>
                    @error('delivery_type') <span class="pmb-error">{{ $message }}</span> @endif
                </div>
            </div>

            <div class="pmb-flex">
                <button class="pmb-btn pmb-btn--gold" type="submit">Continue</button>
                <a class="pmb-btn pmb-btn--ghost" href="{{ route('orders.show', $order) }}">Cancel</a>
            </div>
        </form>
    </div>

    <div class="pmb-card">
        <h2 class="pmb-card__title">Order summary</h2>
        <div class="pmb-money">
            <div class="pmb-money__row"><span>Subtotal</span><span class="pmb-ksh">KSh {{ number_format($order->subtotal, 2) }}</span></div>
            <div class="pmb-money__row"><span>Paid</span><span class="pmb-ksh">KSh {{ number_format($order->amount_paid, 2) }}</span></div>
            <div class="pmb-money__row pmb-money__row--total"><span>Total</span><span class="pmb-ksh">KSh {{ number_format($order->total, 2) }}</span></div>
        </div>
        <p style="color:var(--ink-muted);font-size:.85rem;margin:1rem 0 0;">The delivery charge will be added to your total once you select an area and delivery type.</p>
    </div>
</div>

<script>
(function () {
    const method = document.getElementById('fulfillment_method');
    const delivery = document.getElementById('delivery-options');
    function toggle() {
        delivery.style.display = method.value === 'delivery' ? 'block' : 'none';
    }
    method.addEventListener('change', toggle);
    toggle();
})();
</script>
<style>
.pmb-hint { font-size:.8rem; color:var(--ink-muted); margin:.25rem 0 0; }
</style>
@endsection