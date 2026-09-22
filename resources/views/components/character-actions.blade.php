@props(['character', 'actions', 'realmId'])
@if(collect(\App\Support\CharacterActions::ACTIONS)->contains(fn ($action) => $action->enabled()))
<x-dropdown align="right" width="character-actions-menu" content-classes="character-actions-content">
    <x-slot name="trigger">
        <button type="button" class="character-actions-trigger" :aria-expanded="open" aria-label="Actions for {{ $character->name }}">
            Actions
            <svg width="12" height="12" viewBox="0 0 12 12" fill="none" aria-hidden="true"><path d="m3 4.5 3 3 3-3" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" /></svg>
        </button>
    </x-slot>
    <x-slot name="content">
        @foreach(\App\Support\CharacterActions::ACTIONS as $action)
            @continue(! $action->enabled())
            <button type="button" @disabled($actions[$action->value]['disabled'])
                x-on:click="openAction(@js(['name' => $character->name, 'home' => $character->homebind ? \App\Support\Wow::areaName((int) $character->homebind->zoneId) : null, 'url' => route('characters.actions.store', ['realmId' => $realmId, 'guid' => $character->guid])]), '{{ $action->value }}')">
                <span>{{ $action->label() }}</span>
                @if($actions[$action->value]['reason'])<small>{{ $actions[$action->value]['reason'] }}</small>@endif
            </button>
        @endforeach
    </x-slot>
</x-dropdown>
@endif
