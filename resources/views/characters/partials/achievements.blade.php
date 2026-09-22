<form class="character-search achievement-search" method="get">
    <input type="hidden" name="tab" value="achievements">
    <input type="search" name="q" value="{{ $filters['q'] }}" placeholder="Search achievements" aria-label="Search achievements" maxlength="100">
    <select name="category" aria-label="Achievement category"><option value="">All categories</option>@foreach($categories as $id => $label)<option value="{{ $id }}" @selected($filters['category'] === $id)>{{ $label }}</option>@endforeach</select>
    <select name="status" aria-label="Achievement status">@foreach(['earned' => 'Earned', 'missing' => 'Not earned', 'all' => 'All achievements'] as $key => $label)<option value="{{ $key }}" @selected($filters['status'] === $key)>{{ $label }}</option>@endforeach</select>
    <button type="submit" class="game-button">Search</button>
</form>
<div class="achievement-summary">{{ number_format($achievementCount) }} / {{ number_format($achievementTotal) }} earned <span>{{ number_format($achievementPoints) }} points</span></div>
<div class="achievement-list">@forelse($achievements as $achievement)<x-character-achievement :achievement="$achievement" :tooltip-url="$tooltipUrl" />@empty<p class="character-empty">No achievements match your search.</p>@endforelse</div>
<footer class="game-footer"><span>{{ number_format($achievements->total()) }} achievements</span><nav aria-label="Achievement pages">@if(!$achievements->onFirstPage())<a class="game-page" href="{{ $achievements->previousPageUrl() }}" rel="prev">Previous</a>@endif<span>{{ $achievements->currentPage() }} / {{ $achievements->lastPage() }}</span>@if($achievements->hasMorePages())<a class="game-page" href="{{ $achievements->nextPageUrl() }}" rel="next">Next</a>@endif</nav></footer>
