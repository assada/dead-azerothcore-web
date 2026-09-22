@props(['heading' => 'h2', 'id', 'title', 'url', 'columns', 'order', 'entry' => 'character', 'entries' => 'characters', 'search' => 'Find a character'])
<section {{ $attributes->class(['game-window', 'data-table']) }} data-table data-url="{{ $url }}" data-order="{{ json_encode($order) }}" data-entry="{{ $entry }}" data-entries="{{ $entries }}" data-search="{{ $search }}" aria-label="{{ $title }}">
    <div class="table-heading">
        <{{ $heading }}>{{ $title }}</{{ $heading }}>
        @if(collect($columns)->where('mobile', true)->count() > 1)
            <select class="table-column" aria-label="Sort {{ $entries }} by">
                @foreach($columns as $idx => $column)
                    @if($column['mobile'] ?? false)<option value="{{ $idx }}">{{ $column['label'] }}</option>@endif
                @endforeach
            </select>
        @endif
    </div>
    @isset($filters)<div class="table-filters">{{ $filters }}</div>@endisset
    <div class="table-error" role="alert" hidden><span>Could not load {{ $entries }}.</span> <button type="button" data-retry>Retry</button> <a href="{{ route('login') }}" data-login hidden>Log in</a></div>
    <table id="{{ $id }}" class="realm-table">
        <thead><tr>
            @foreach($columns as $column)
                <th scope="col" data-key="{{ $column['key'] }}" data-sort="{{ $column['sort'] ?? 'asc' }}" @class([$column['class'] ?? '', 'mobile-value' => $column['mobile'] ?? false])>{{ $column['label'] }}</th>
            @endforeach
        </tr></thead>
        <tbody></tbody>
    </table>
    <noscript><p class="table-empty">Enable JavaScript to view {{ $entries }}.</p></noscript>
</section>
