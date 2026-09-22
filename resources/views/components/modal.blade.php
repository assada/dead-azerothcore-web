@props(['name', 'show' => false])
<dialog class="account-dialog" aria-labelledby="{{ $name }}-title"
    x-data
    x-init="if (@js($show)) $nextTick(() => $el.showModal())"
    x-on:open-modal.window="if ($event.detail === '{{ $name }}' && ! $el.open) $el.showModal()"
    x-on:close-modal.window="if ($event.detail === '{{ $name }}') $el.close()"
    x-on:close.stop="$el.close()"
    x-on:click="if ($event.target === $el) $el.close()">
    {{ $slot }}
</dialog>
