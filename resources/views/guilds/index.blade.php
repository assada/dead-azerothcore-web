<x-app-layout>
    <div class="community-page">
        <x-data-table heading="h1" id="guilds" title="Guilds" :url="route('guilds.index')" :order="[0, 'asc']" entry="guild" entries="guilds" search="Find a guild" :columns="[
            ['key' => 'guild', 'label' => 'Guild', 'class' => 'table-guild'],
            ['key' => 'leader', 'label' => 'Leader', 'sort' => 'none', 'class' => 'mobile-secondary'],
            ['key' => 'members', 'label' => 'Members', 'class' => 'table-number', 'sort' => 'desc'],
        ]" />
    </div>
</x-app-layout>
