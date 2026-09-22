@if(config('site.logo'))
    <img src="{{ asset(config('site.logo')) }}" alt="{{ config('app.name') }}" {{ $attributes->class(['h-14']) }}>
@else
    <span {{ $attributes->class(['font-semibold']) }}>{{ config('app.name') }}</span>
@endif
