@props(['guild'])
@if($guild)
    <a class="guild-link" href="{{ route('guilds.show', $guild->guildid) }}">{{ $guild->name }}</a>
@else
    <span class="table-muted">—</span>
@endif
