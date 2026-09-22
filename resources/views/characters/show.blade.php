<x-app-layout>
    @php
        $classColor = \App\Support\Wow::classColors()[$character->class] ?? '#fff';
        $url = fn (array $params = []) => route('characters.show', ['realm' => $realm, 'name' => $character->name] + $params);
        $iconBase = $tooltipUrl.'/static/images/wow/icons/medium/';
    @endphp
    <div class="character-page" data-tab="{{ $tab }}" style="--class-color: {{ $classColor }}" x-data="characterActions">
        @include('characters.action-feedback')
        <div class="character-toolbar">
            <a class="character-back" href="{{ route('characters.index', ['realm' => $realm]) }}">‹ Characters</a>
            @if($actions)
                <x-character-actions :character="$character" :actions="$actions" :realm-id="$realmId" />
            @endif
        </div>
        <header class="character-header">
            <img class="character-faction" src="{{ asset('images/'.\App\Support\Wow::factionByRace($character->race).'.png') }}" alt="{{ ucfirst(\App\Support\Wow::factionByRace($character->race)) }}" width="48" height="48">
            <div class="character-identity">
                <h1>{{ $character->name }} <span class="character-status {{ $character->online ? 'is-online' : '' }}" title="{{ $character->online ? 'Online' : 'Offline' }}" aria-label="{{ $character->online ? 'Online' : 'Offline' }}"></span></h1>
                <p>Level {{ $character->level }} {{ \App\Support\Wow::raceNames()[$character->race] ?? '' }} {{ \App\Support\Wow::classNames()[$character->class] ?? '' }} <span>· {{ $realm }}</span></p>
                @if($character->guild?->guild)<a class="character-guild" href="{{ route('guilds.show', $character->guild->guild->guildid) }}">&lt;{{ $character->guild->guild->name }}&gt;</a>@endif
            </div>
            <dl class="character-scores">
                <div><dt>GearScore</dt><dd>{{ number_format($gearScore) }}</dd></div>
                <div><dt>Item level</dt><dd>{{ $itemLevel }}</dd></div>
                <div><dt>Achievement points</dt><dd>{{ number_format($achievementPoints) }}</dd></div>
            </dl>
        </header>
        <section class="character-window">
            <nav class="character-tabs" aria-label="Character sections">
                @foreach(['equipment' => 'Equipment', 'talents' => 'Talents', 'mounts' => 'Mounts', 'achievements' => 'Achievements', 'pvp' => 'PvP'] as $key => $label)
                    <a href="{{ $url(['tab' => $key]) }}" class="{{ $tab === $key ? 'is-active' : '' }}" @if($tab === $key) aria-current="page" @endif>{{ $label }}@if($key === 'mounts')<span>{{ $mountCount }}</span>@endif</a>
                @endforeach
            </nav>
            @include('characters.partials.'.$tab)
        </section>
        @if($actions)
            @include('characters.action-modal')
        @endif
    </div>
    <x-game-tooltips :url="$tooltipUrl" />
    @isset($model)
        <script type="application/json" id="character-model-data">{!! json_encode($model, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}</script>
        <script src="{{ asset('vendor/modelviewer/viewer.min.js') }}" defer></script>
    @endisset
</x-app-layout>
