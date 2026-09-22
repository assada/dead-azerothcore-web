@props(['amount'])
@php([$gold, $silver, $copper] = \App\Support\Wow::formatMoney((int) $amount))
<span class="money" aria-label="{{ $gold }} gold, {{ $silver }} silver, {{ $copper }} copper">
    @if($gold)<span>{{ number_format($gold) }}<i class="coin coin-gold" aria-hidden="true"></i></span>@endif
    @if($silver || $gold)<span>{{ $silver }}<i class="coin coin-silver" aria-hidden="true"></i></span>@endif
    <span>{{ $copper }}<i class="coin coin-copper" aria-hidden="true"></i></span>
</span>
