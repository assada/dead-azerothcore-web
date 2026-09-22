@props(['achievement', 'tooltipUrl'])
<a class="character-achievement {{ $achievement['date'] ? 'is-earned' : '' }}" href="{{ $tooltipUrl }}/?achievement={{ $achievement['id'] }}" rel="achievement={{ $achievement['id'] }}" target="_blank">
    <img src="{{ $tooltipUrl }}/static/images/wow/icons/medium/{{ $achievement['icon'] }}.jpg" alt="" width="40" height="40" loading="lazy">
    <div><h3>{{ $achievement['title'] }}</h3><p>{{ $achievement['description'] }}</p>@if($achievement['date'])<time datetime="{{ date('Y-m-d', $achievement['date']) }}">{{ date('d M Y', $achievement['date']) }}</time>@endif</div>
    <strong class="achievement-points" aria-label="{{ $achievement['points'] }} points">{{ $achievement['points'] }}</strong>
</a>
