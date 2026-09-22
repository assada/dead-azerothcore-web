@props(['character', 'realmId' => \App\Support\Wow::defaultRealmId(), 'showOnline' => false])
@php
    $class = \App\Support\Wow::classNames()[$character->class] ?? 'Unknown';
    $race = \App\Support\Wow::raceNames()[$character->race] ?? '';
    $color = \App\Support\Wow::classColors()[$character->class] ?? '#e3dac8';
    $faction = \App\Support\Wow::factionByRace((int) $character->race);
@endphp
<a class="character-summary" style="--class-color: {{ $color }}" href="{{ route('characters.show', ['realm' => \App\Support\Wow::realmParamFromId($realmId), 'name' => $character->name]) }}">
    <img src="{{ asset('images/'.$faction.'.png') }}" alt="{{ ucfirst($faction) }}" width="22" height="26">
    <span><strong>{{ $character->name }}@if($showOnline && $character->online)<span class="online-dot" role="img" aria-label="Online" title="Online"></span>@endif</strong><small>{{ $character->level }} {{ $race }} {{ $class }}</small></span>
</a>
