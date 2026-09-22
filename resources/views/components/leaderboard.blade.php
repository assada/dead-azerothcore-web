@props(['heading' => 'h2'])
<x-data-table :heading="$heading" id="rankings" title="Rankings" :url="route('leaderboard')" :order="[2, 'desc']" :columns="[
    ['key' => 'rank', 'label' => '#', 'class' => 'table-rank', 'sort' => 'none'],
    ['key' => 'character', 'label' => 'Character', 'class' => 'table-character', 'sort' => 'none'],
    ['key' => 'gold', 'label' => 'Gold', 'class' => 'table-number', 'sort' => 'desc', 'mobile' => true],
    ['key' => 'played', 'label' => 'Played', 'class' => 'table-number', 'sort' => 'desc', 'mobile' => true],
    ['key' => 'honor', 'label' => 'Honor', 'class' => 'table-number', 'sort' => 'desc', 'mobile' => true],
    ['key' => 'arena', 'label' => 'Arena', 'class' => 'table-number', 'sort' => 'desc', 'mobile' => true],
]" />
