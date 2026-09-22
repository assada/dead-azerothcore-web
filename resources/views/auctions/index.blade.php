<x-app-layout>
    <x-slot name="header">
        <x-page-heading title="Auction House" />
    </x-slot>

    @php
        $url = fn (array $changes) => route('auctions.index', array_filter(array_replace($filters, ['market' => $market, 'page' => null], $changes), fn ($value) => $value !== null && $value !== ''));
        $category = $filters['category'] ?? null;
        $subclass = $filters['subclass'] ?? null;
        $iconBase = $tooltipUrl.'/static/images/wow/icons/medium/';
    @endphp

    <div class="max-w-7xl mx-auto px-3 sm:px-6 lg:px-8 py-6">
        <section class="auction-window game-window" aria-label="Auction browser">
            <nav class="auction-markets" aria-label="Auction markets">
                @foreach($markets as $key => $meta)
                    <a href="{{ $url(['market' => $key]) }}" class="auction-market market-{{ $key }} {{ $market === $key ? 'is-active' : '' }}" @if($market === $key) aria-current="page" @endif>
                        @if(in_array($key, ['alliance', 'horde']))
                            <img src="{{ $iconBase }}{{ $key === 'alliance' ? 'inv_bannerpvp_02' : 'inv_bannerpvp_01' }}.jpg" alt="" width="24" height="24">
                        @else
                            <img src="{{ $iconBase }}inv_misc_coin_01.jpg" alt="" width="24" height="24">
                        @endif
                        {{ $meta['name'] }}
                    </a>
                @endforeach
            </nav>

            <div class="auction-body">
                <details class="auction-categories" open x-data x-init="$el.open = window.matchMedia('(min-width: 640px)').matches">
                    <summary>Categories</summary>
                    <nav aria-label="Item categories">
                        <a class="auction-category {{ $category === null ? 'is-active' : '' }}" href="{{ $url(['category' => null, 'subclass' => null]) }}" @if($category === null) aria-current="true" @endif>All items</a>
                        @foreach($categories as $id => $meta)
                            <a class="auction-category {{ (string) $category === (string) $id ? 'is-active' : '' }}" href="{{ $url(['category' => $id, 'subclass' => null]) }}" @if((string) $category === (string) $id) aria-current="true" @endif>
                                <img src="{{ $iconBase.$meta['icon'] }}.jpg" alt="" width="22" height="22" loading="lazy">
                                {{ $meta['name'] }}
                                @if($meta['subclasses'])<span class="category-arrow" aria-hidden="true">{{ (string) $category === (string) $id ? '−' : '+' }}</span>@endif
                            </a>
                            @if((string) $category === (string) $id)
                                <div class="auction-subcategories">
                                    @foreach($meta['subclasses'] as $subId => $name)
                                        <a class="{{ (string) $subclass === (string) $subId ? 'is-active' : '' }}" href="{{ $url(['subclass' => $subId]) }}" @if((string) $subclass === (string) $subId) aria-current="true" @endif>{{ $name }}</a>
                                    @endforeach
                                </div>
                            @endif
                        @endforeach
                    </nav>
                </details>

                <div class="auction-results">
                    <form class="auction-search" action="{{ route('auctions.index') }}" method="get" role="search">
                        <input type="hidden" name="market" value="{{ $market }}">
                        @if($category !== null)<input type="hidden" name="category" value="{{ $category }}">@endif
                        @if($subclass !== null)<input type="hidden" name="subclass" value="{{ $subclass }}">@endif
                        <label class="auction-name-filter">Name
                            <input type="search" name="q" value="{{ $filters['q'] ?? '' }}" maxlength="100" placeholder="Search items" autocomplete="off">
                        </label>
                        <fieldset class="auction-level-filter">
                            <legend>Level</legend>
                            <div><input type="number" name="min_level" aria-label="Minimum level" min="0" max="80" value="{{ $filters['min_level'] ?? '' }}" placeholder="1"><span aria-hidden="true">–</span><input type="number" name="max_level" aria-label="Maximum level" min="0" max="80" value="{{ $filters['max_level'] ?? '' }}" placeholder="80"></div>
                        </fieldset>
                        <label class="auction-quality-filter">Quality
                            <select name="quality">
                                <option value="">All qualities</option>
                                @foreach($qualities as $id => $name)<option value="{{ $id }}" @selected(isset($filters['quality']) && (int) $filters['quality'] === $id)>{{ $name }}</option>@endforeach
                            </select>
                        </label>
                        <button type="submit" class="game-button">Search</button>
                    </form>
                    @if($errors->any())<div class="auction-errors" role="alert">{{ $errors->first() }}</div>@endif

                    <div class="auction-result-bar">
                        <span>{{ $category !== null ? $categories[$category]['name'] : 'All items' }}@if($subclass !== null) / {{ $categories[$category]['subclasses'][$subclass] }}@endif</span>
                        @if(collect($filters)->except(['market', 'sort', 'direction', 'page'])->filter(fn ($value) => $value !== null && $value !== '')->isNotEmpty())
                            <a href="{{ route('auctions.index', ['market' => $market]) }}">Clear filters</a>
                        @endif
                    </div>

                    <div class="auction-table-scroll">
                        <table class="auction-table">
                            <thead><tr>
                                @foreach(['name' => 'Item', 'level' => 'Level', 'time' => 'Time left', 'seller' => 'Seller', 'bid' => 'Bid', 'buyout' => 'Buyout'] as $key => $label)
                                    <th scope="col" class="column-{{ $key }}" @if($key === $sort) aria-sort="{{ $direction === 'asc' ? 'ascending' : 'descending' }}" @endif>
                                        @if($key === 'seller'){{ $label }}@else
                                            <a href="{{ $url(['sort' => $key, 'direction' => $sort === $key && $direction === 'asc' ? 'desc' : 'asc']) }}">{{ $label }}@if($sort === $key)<span aria-hidden="true"> {{ $direction === 'asc' ? '↑' : '↓' }}</span>@endif</a>
                                        @endif
                                    </th>
                                @endforeach
                            </tr></thead>
                            <tbody>
                                @forelse($auctions as $auction)
                                    <tr>
                                        <td class="column-name">
                                            <a class="auction-item quality-{{ $auction->quality }}" href="{{ $tooltipUrl }}/?item={{ $auction->itemEntry }}" rel="{{ $auction->tooltipRel() }}" target="_blank">
                                                <span class="auction-item-icon"><img src="{{ $iconBase.($icons[$auction->displayid] ?? 'inv_misc_questionmark') }}.jpg" width="40" height="40" alt="" loading="lazy">@if($auction->count > 1)<span>{{ $auction->count }}</span>@endif</span>
                                                <span>{{ $auction->name }}</span>
                                            </a>
                                            <span class="auction-mobile-detail">Lv {{ $auction->level ?: 1 }} · {{ $auction->timeLeft() }} · {{ $auction->seller ?? 'Unknown' }}</span>
                                        </td>
                                        <td class="column-level">{{ $auction->level ?: '—' }}</td>
                                        <td class="column-time"><span title="{{ \Carbon\Carbon::createFromTimestamp($auction->time)->utc()->format('Y-m-d H:i') }} UTC">{{ $auction->timeLeft() }}</span></td>
                                        <td class="column-seller">{{ $auction->seller ?? 'Unknown' }}</td>
                                        <td class="column-bid"><x-money :amount="$auction->bid" /></td>
                                        <td class="column-buyout">@if($auction->buyoutprice)<x-money :amount="$auction->buyoutprice" />@else<span class="auction-no-buyout">—</span>@endif<span class="auction-mobile-bid">Bid <x-money :amount="$auction->bid" /></span></td>
                                    </tr>
                                @empty
                                    <tr><td colspan="6" class="auction-empty">{{ $filters ? 'No auctions match your search.' : 'No auctions available.' }}</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <footer class="game-footer">
                        <span>@if($auctions->total()){{ $auctions->firstItem() }}–{{ $auctions->lastItem() }} of @endif{{ number_format($auctions->total()) }} auctions</span>
                        <nav aria-label="Auction pages">
                            @if($auctions->onFirstPage())<span class="game-page is-disabled" aria-disabled="true">Previous</span>@else<a class="game-page" href="{{ $auctions->previousPageUrl() }}" rel="prev">Previous</a>@endif
                            <span>{{ $auctions->currentPage() }} / {{ $auctions->lastPage() }}</span>
                            @if($auctions->hasMorePages())<a class="game-page" href="{{ $auctions->nextPageUrl() }}" rel="next">Next</a>@else<span class="game-page is-disabled" aria-disabled="true">Next</span>@endif
                        </nav>
                    </footer>
                </div>
            </div>
        </section>
    </div>
    <x-game-tooltips :url="$tooltipUrl" />
</x-app-layout>
