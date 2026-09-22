<div class="character-equipment">
    <div class="paperdoll">
        <div class="equipment-column equipment-left">
            @foreach([0, 1, 2, 14, 4, 3, 18, 8] as $slot)<x-equipment-slot :slot-id="$slot" :item="$equipment[$slot] ?? null" :tooltip-url="$tooltipUrl" />@endforeach
        </div>
        @include('characters.partials.model')
        <div class="equipment-column equipment-right">
            @foreach([9, 5, 6, 7, 10, 11, 12, 13] as $slot)<x-equipment-slot :slot-id="$slot" :item="$equipment[$slot] ?? null" :tooltip-url="$tooltipUrl" />@endforeach
        </div>
        <div class="equipment-weapons">@foreach([15, 16, 17] as $slot)<x-equipment-slot :slot-id="$slot" :item="$equipment[$slot] ?? null" :tooltip-url="$tooltipUrl" />@endforeach</div>
    </div>
    @include('characters.partials.stats')
</div>
<div class="character-ledger">
    <div><span>Money</span><x-money :amount="$character->money" /></div>
    <div><span>Played</span><strong>@php([$hours, $minutes] = \App\Support\Wow::formatPlayed($character->totaltime)){{ number_format($hours) }}h {{ $minutes }}m</strong></div>
    <div><span>Quests completed</span><strong>{{ number_format($character->completed_quests_count) }}</strong></div>
    <div><span>Last online</span><strong>@if($character->online)Online now @elseif($character->logout_time){{ \Carbon\Carbon::createFromTimestamp($character->logout_time)->diffForHumans() }}@else — @endif</strong></div>
</div>
<div class="character-details">
    <section class="character-skills"><h2>Skills &amp; professions</h2>
        @foreach($skills as $label => $rows)
            @if($rows->isNotEmpty())
                <x-character-disclosure :label="$label" :count="$rows->count()" :open="$label === 'Professions'">
                    @foreach($rows as $skill)<x-character-progress :name="$skill['name']" :value="$skill['value']" :max="$skill['max']" color="#478bb8" />@endforeach
                </x-character-disclosure>
            @endif
        @endforeach
    </section>
    <section class="character-reputations"><h2>Reputation</h2>
        @forelse(array_filter($reputations) as $expansion => $rows)
            <x-character-disclosure :label="$expansion" :count="count($rows)" :open="$loop->first">
                @foreach($rows as $rep)<x-character-progress :name="$rep['name']" :value="$rep['value']" :max="$rep['max']" :color="$rep['color']" :label="$rep['standing']" />@endforeach
            </x-character-disclosure>
        @empty<p class="character-empty">No reputations yet.</p>@endforelse
    </section>
</div>
@if($recentAchievements->isNotEmpty())
    <section class="character-recent"><div class="character-section-heading"><h2>Recent achievements</h2><a href="{{ $url(['tab' => 'achievements']) }}">View all ›</a></div>
        @foreach($recentAchievements as $achievement)<x-character-achievement :achievement="$achievement" :tooltip-url="$tooltipUrl" />@endforeach
    </section>
@endif
