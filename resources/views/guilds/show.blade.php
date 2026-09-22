<x-app-layout>
    <div class="community-page guild-page">
        <a class="page-back" href="{{ route('guilds.index') }}">‹ Guilds</a>
        <header class="guild-header">
            <div>
                <h1>{{ $guild->name }}</h1>
                @if($leader)<div class="guild-leader"><span>Guild Master</span><x-character-summary :character="$leader" /></div>@endif
            </div>
            <dl class="guild-counts"><div><dt>Members</dt><dd>{{ number_format($memberCount) }}</dd></div><div><dt>Online</dt><dd>{{ number_format($onlineCount) }}</dd></div></dl>
        </header>
        <x-data-table id="guild-members" title="Members" :url="route('guilds.show', $guild->guildid)" :order="[1, 'asc']" entry="member" entries="members" search="Find a member" :columns="[
            ['key' => 'character', 'label' => 'Character', 'class' => 'table-character'],
            ['key' => 'rank', 'label' => 'Rank'],
            ['key' => 'level', 'label' => 'Level', 'class' => 'table-number mobile-secondary', 'sort' => 'desc'],
            ['key' => 'played', 'label' => 'Played', 'class' => 'table-number mobile-secondary', 'sort' => 'desc'],
        ]" />
    </div>
</x-app-layout>
