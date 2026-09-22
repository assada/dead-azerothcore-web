@props(['character', 'actions' => [], 'realmId' => null])
@php([$hours, $minutes] = \App\Support\Wow::formatPlayed($character->totaltime))
<article class="my-character" style="--class-color: {{ \App\Support\Wow::classColors()[$character->class] ?? '#c8b687' }}">
    <div class="my-character-identity">
        <x-character-summary :character="$character" :show-online="true" />
        @if($character->guild?->guild)<x-guild-link :guild="$character->guild->guild" />@endif
    </div>
    <dl class="my-character-stats">
        <div><dt>Played</dt><dd>{{ $hours }}h {{ $minutes }}m</dd></div>
        <div><dt>Honor</dt><dd>{{ number_format($character->totalHonorPoints) }}</dd></div>
        <div><dt>Gold</dt><dd><x-money :amount="$character->money" /></dd></div>
    </dl>
    @if($actions)
        <div class="my-character-actions">
            <x-character-actions :character="$character" :actions="$actions" :realm-id="$realmId" />
        </div>
    @endif
</article>
