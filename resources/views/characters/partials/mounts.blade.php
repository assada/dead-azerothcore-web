<div class="character-mounts">
    @include('characters.partials.model')
    <section class="mount-collection" aria-label="Mount collection">
        <form class="character-search" method="get"><input type="hidden" name="tab" value="mounts"><input type="search" name="q" value="{{ $search }}" aria-label="Search mounts" placeholder="Search mounts" maxlength="100"><button type="submit" class="game-button">Search</button></form>
        <div class="mount-list">
            @forelse($mounts as $mount)
                <button class="mount-item" type="button" x-data="{ selected: false }" @mount-selected.window="selected = $event.detail.display === {{ $mount['display'] }}" :class="{ 'is-active': selected }" :aria-pressed="selected" @click="$dispatch('mount-selected', {{ json_encode(['display' => $mount['display'], 'name' => $mount['name']]) }})">
                    <img src="{{ $iconBase.$mount['icon'] }}.jpg" alt="" width="36" height="36" loading="lazy"><span>{{ $mount['name'] }}</span>
                </button>
            @empty<p class="character-empty">{{ $search ? 'No mounts match your search.' : 'No mounts learned yet.' }}</p>@endforelse
        </div>
    </section>
</div>
