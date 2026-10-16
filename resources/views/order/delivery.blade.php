@extends('layouts.customer')

@section('title', 'Delivery — ' . $order->reference)

@section('content')
<div class="pmb-page-title">
    <div class="pmb-row">
        <div>
            <h1 class="pmb-h1">Delivery options</h1>
            <p>{{ $order->reference }}</p>
        </div>
    </div>
</div>

<div class="pmb-grid pmb-grid--main">
    <div class="pmb-card">
        <h2 class="pmb-card__title">Where will PMB deliver?</h2>
        <p style="color:var(--ink-muted);font-size:.9rem;margin:0 0 1rem;">Choose your delivery area and how PMB should reach you.</p>

        <form method="POST" action="{{ route('orders.delivery.set', $order) }}" class="pmb-form">
            @csrf

            <div class="pmb-field">
                <label class="pmb-label" for="delivery_zone_id">Delivery area *</label>
                <select class="pmb-input" id="delivery_zone_id" name="delivery_zone_id" required>
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
                <select class="pmb-input" id="delivery_type" name="delivery_type" required>
                    <option value="">— Select type —</option>
                    @foreach(DeliveryType::cases() as $type)
                        <option value="{{ $type->value }}">{{ $type->label() }}</option>
                    @endforeach
                </select>
                <p class="pmb-hint">Standard = zone base charge only. Door-to-door adds the door-to-door surcharge. Beyond stage adds the beyond-stage surcharge.</p>
                @error('delivery_type') <span class="pmb-error">{{ $message }}</span> @endif
            </div>

            <div class="pmb-flex">
                <button class="pmb-btn pmb-btn--gold" type="submit">Save delivery options</button>
                <a class="pmb-btn pmb-btn--ghost" href="{{ route('orders.show', $order) }}">Cancel</a>
            </div>
        </form>
    </div>

    <div class="pmb-card">
        <h2 class="pmb-card__title">Current delivery</h2>
        @if($order->deliveryZone)
            <div class="pmb-money">
                <div class="pmb-money__row"><span>Area</span><span>{{ $order->deliveryZone->name }}</span></div>
                <div class="pmb-money__row"><span>Type</span><span>{{ $order->delivery_type ? \App\Enums\DeliveryType::from($order->delivery_type)->label() : '—' }}</span></div>
                <div class="pmb-money__row"><span>Delivery fee</span><span class="pmb-ksh">KSh {{ number_format($order->delivery_fee, 2) }}</span></div>
            </div>
        @else
            <p style="color:var(--ink-muted);font-size:.9rem;margin:0;">No delivery area selected yet.</p>
        @endif

        <div class="pmb-money" style="margin-top:1rem;">
            <div class="pmb-money__row"><span>Subtotal</span><span class="pmb-ksh">KSh {{ number_format($order->subtotal, 2) }}</span></div>
            <div class="pmb-money__row"><span>Delivery fee</span><span class="pmb-ksh">KSh {{ number_format($order->delivery_fee, 2) }}</span></div>
            <div class="pmb-money__row pmb-money__row--total"><span>Total</span><span class="pmb-ksh">KSh {{ number_format($order->total, 2) }}</span></div>
        </div>
    </div>
</div>
<style>
.pmb-hint { font-size:.8rem; color:var(--ink-muted); margin:.25rem 0 0; }
</style>