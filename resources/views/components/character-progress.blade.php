@props(['name', 'value', 'max', 'color', 'label' => null])
<div class="character-progress">
    <div><span>{{ $name }}</span><span title="{{ $value }} / {{ $max }}">{{ $label ?? ($value.' / '.$max) }}</span></div>
    <meter min="0" max="{{ max(1, $max) }}" value="{{ $value }}" aria-label="{{ $name }}" style="--bar-color: {{ $color }}">{{ $value }} / {{ $max }}</meter>
</div>
