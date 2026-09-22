<x-app-layout>
    <div class="community-page">
        <x-data-table heading="h1" id="characters" title="Characters" :url="route('characters.index', ['realm' => \App\Support\Wow::realmParamFromId($realmId)])" :order="[2, 'desc']" :columns="[
            ['key' => 'character', 'label' => 'Character', 'class' => 'table-character'],
            ['key' => 'guild', 'label' => 'Guild', 'class' => 'mobile-secondary', 'sort' => 'none'],
            ['key' => 'level', 'label' => 'Level', 'class' => 'table-number', 'sort' => 'desc', 'mobile' => true],
            ['key' => 'played', 'label' => 'Played', 'class' => 'table-number', 'sort' => 'desc', 'mobile' => true],
            ['key' => 'honor', 'label' => 'Honor', 'class' => 'table-number', 'sort' => 'desc', 'mobile' => true],
            ['key' => 'gold', 'label' => 'Gold', 'class' => 'table-number', 'sort' => 'desc', 'mobile' => true],
        ]">
            <x-slot name="filters"><x-character-filters :classes="$classes" /></x-slot>
        </x-data-table>
    </div>
</x-app-layout>
