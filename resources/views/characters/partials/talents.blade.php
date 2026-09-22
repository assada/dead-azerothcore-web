<nav class="character-specs" aria-label="Talent specialization">
    @foreach($specs as $i => $build)<a href="{{ $url(['tab' => 'talents', 'spec' => $i]) }}" class="{{ $spec === $i ? 'is-active' : '' }}" @if($spec === $i) aria-current="true" @endif>{{ $i === 0 ? 'Primary' : 'Secondary' }} <span>{{ implode(' / ', array_column($build['trees'], 'points')) }}</span>@if($build['active'])<small>Active</small>@endif</a>@endforeach
</nav>
<div class="talent-trees">
    @foreach($specs[$spec]['trees'] as $tree)
        <section class="talent-tree"><header><img src="{{ $iconBase.$tree['icon'] }}.jpg" alt="" width="30" height="30"><h2>{{ $tree['name'] }}</h2><strong>{{ $tree['points'] }}</strong></header>
            <div class="talent-grid">
                <svg class="talent-arrows" viewBox="0 0 400 1100" preserveAspectRatio="none" aria-hidden="true">
                    @php($byId = array_column($tree['talents'], null, 'id'))
                    @foreach($tree['talents'] as $talent)
                        @if(isset($byId[$talent['prerequisite']]))
                            @php($parent = $byId[$talent['prerequisite']])
                            <path d="M {{ $parent['column'] * 100 + 50 }} {{ $parent['row'] * 100 + 50 }} V {{ $talent['row'] * 100 + 50 }} H {{ $talent['column'] * 100 + 50 }}" class="{{ $talent['rank'] ? 'is-learned' : '' }}" />
                        @endif
                    @endforeach
                </svg>
                @foreach($tree['talents'] as $talent)
                    <a href="{{ $tooltipUrl }}/?spell={{ $talent['spell'] }}" target="_blank" rel="spell={{ $talent['spell'] }}" class="talent-icon {{ $talent['rank'] ? 'is-learned' : '' }} {{ $talent['rank'] === count($talent['ranks']) ? 'is-maxed' : '' }}" style="grid-row: {{ $talent['row'] + 1 }}; grid-column: {{ $talent['column'] + 1 }}" aria-label="{{ $talent['name'] }}: {{ $talent['rank'] }} of {{ count($talent['ranks']) }}">
                        <img src="{{ $iconBase.$talent['icon'] }}.jpg" alt="" width="40" height="40"><span>{{ $talent['rank'] }}/{{ count($talent['ranks']) }}</span>
                    </a>
                @endforeach
            </div>
        </section>
    @endforeach
</div>
<section class="character-glyphs"><h2>Glyphs</h2><div>
    @foreach($specs[$spec]['glyphs'] as $label => $glyphs)<section><h3>{{ $label }}</h3>@forelse($glyphs as $glyph)<a href="{{ $tooltipUrl }}/?spell={{ $glyph['spell'] }}" rel="spell={{ $glyph['spell'] }}" target="_blank"><img src="{{ $iconBase.$glyph['icon'] }}.jpg" alt="" width="30" height="30">{{ $glyph['name'] }}</a>@empty<p class="character-empty">No {{ strtolower($label) }} glyphs.</p>@endforelse</section>@endforeach
</div></section>
