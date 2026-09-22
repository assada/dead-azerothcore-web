@props(['slotId', 'item' => null, 'tooltipUrl'])
@if($item)
    <a class="equipment-slot quality-{{ $item['quality'] }}" href="{{ $tooltipUrl }}/?item={{ $item['itemEntry'] }}" rel="item={{ $item['itemEntry'] }}&amp;{{ $item['tooltip'] }}" target="_blank" aria-label="{{ $item['name'] }}, {{ $item['slotName'] }}, item level {{ $item['itemLevel'] }}">
        <span class="equipment-icon"><img src="{{ $tooltipUrl }}/static/images/wow/icons/medium/{{ $item['icon'] }}.jpg" alt="" width="42" height="42"></span>
        <span class="equipment-label"><strong>{{ $item['name'] }}</strong><small>{{ $item['itemLevel'] }} <span>· {{ $item['slotName'] }}</span></small></span>
    </a>
@else
    <span class="equipment-slot is-empty" title="{{ \App\Support\Equipment::SLOTS[$slotId] }}: empty">
        <span class="equipment-icon"><img src="{{ asset('images/inventory-slot/'.$slotId.'.png') }}" alt="" width="42" height="42"></span>
        <span class="equipment-label">{{ \App\Support\Equipment::SLOTS[$slotId] }}</span>
    </span>
@endif
