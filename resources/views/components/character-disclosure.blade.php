@props(['label', 'count' => null, 'open' => false, 'divider' => false])

<details {{ $attributes->class(['character-disclosure', 'character-disclosure--divider' => $divider]) }} @if($open) open @endif>
    <summary>
        <span class="disclosure-heading">
            <span>{{ $label }}</span>
            @if($count !== null)<span class="disclosure-count">{{ $count }}</span>@endif
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m6 9 6 6 6-6" /></svg>
        </span>
    </summary>
    {{ $slot }}
</details>
