{{--
    Journey stepper (canonical "YOUR PMB JOURNEY").
    Expects: $stages = array of ['key','label','state'=>'done|current|pending','meta'?]
--}}
<div class="pmb-steps">
    @foreach($stages as $idx => $step)
        <div class="pmb-step {{ $step['state'] === 'done' ? 'is-done' : ($step['state'] === 'current' ? 'is-current' : 'is-pending') }}">
            <div class="pmb-step__dot-wrap">
                <div class="pmb-step__dot">
                    @if($step['state'] === 'done') ✓ @endif
                </div>
                @unless($loop->last)
                    <div class="pmb-step__line"></div>
                @endunless
            </div>
            <div class="pmb-step__body">
                <div class="pmb-step__label">
                    {!! $step['state'] === 'current' ? '<strong>' : '' !!}{{ $step['label'] }}{!! $step['state'] === 'current' ? '</strong>' : '' !!}
                </div>
                @if(!empty($step['meta']))
                    <div class="pmb-step__sup">{{ $step['meta'] }}</div>
                @endif
            </div>
        </div>
    @endforeach
</div>
