<x-app-layout>
    <div class="community-page" x-data="characterActions">
        @include('characters.action-feedback')
        @if($characters->isNotEmpty())
            <section class="my-characters" aria-labelledby="my-characters-title">
                <div class="my-characters-heading">
                    <h1 id="my-characters-title">My characters</h1>
                    <a href="{{ route('profile.history') }}">Operation history</a>
                </div>
                <div class="my-character-list">
                    @foreach($characters as $character)
                        <x-character-card :character="$character" :actions="$actions[$character->guid]" :realm-id="$realmId" />
                    @endforeach
                </div>
            </section>
        @endif
        <x-leaderboard :heading="$characters->isEmpty() ? 'h1' : 'h2'" />
        @include('characters.action-modal')
    </div>
</x-app-layout>
