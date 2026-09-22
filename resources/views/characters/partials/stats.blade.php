<section class="character-stats" aria-label="Character stats">
    @php
        $stats = $character->stats;
        $groups = [
            'Base stats' => ['strength' => 'Strength', 'agility' => 'Agility', 'stamina' => 'Stamina', 'intellect' => 'Intellect', 'spirit' => 'Spirit'],
            'Melee' => ['attackPower' => 'Attack power', 'critPct' => 'Critical chance'],
            'Ranged' => ['rangedAttackPower' => 'Attack power', 'rangedCritPct' => 'Critical chance'],
            'Spell' => ['spellPower' => 'Spell power', 'spellCritPct' => 'Critical chance'],
            'Defense' => ['armor' => 'Armor', 'blockPct' => 'Block', 'dodgePct' => 'Dodge', 'parryPct' => 'Parry', 'resilience' => 'Resilience'],
            'Resistance' => ['resHoly' => 'Holy', 'resFire' => 'Fire', 'resNature' => 'Nature', 'resFrost' => 'Frost', 'resShadow' => 'Shadow', 'resArcane' => 'Arcane'],
        ];
        $format = fn ($field) => str_ends_with($field, 'Pct') ? number_format((float) $stats->$field, 2).'%' : number_format((int) $stats->$field);
    @endphp
    @if($stats)
        @php
            $primary = collect(['strength', 'agility', 'intellect'])->sortByDesc(fn ($field) => $stats->$field)->first();
            $combat = $primary === 'intellect' ? 'Spell' : ($character->class === 3 ? 'Ranged' : 'Melee');
            $power = array_key_first($groups[$combat]);
            $crit = array_key_last($groups[$combat]);
            [$resource, $resourceName, $divisor, $resourceIcon] = match ($character->class) {
                1 => ['maxpower2', 'Rage', 10, 'ability_racial_bloodrage'],
                4 => ['maxpower4', 'Energy', 1, 'spell_nature_lightning'],
                6 => ['maxpower7', 'Runic power', 10, 'spell_deathknight_butcher2'],
                default => ['maxpower1', 'Mana', 1, 'inv_potion_76'],
            };
            $primaryIcon = ['strength' => 'spell_nature_strength', 'agility' => 'ability_rogue_sprint', 'intellect' => 'spell_holy_magicalsentry'][$primary];
            $powerIcon = ['Melee' => 'ability_warrior_battleshout', 'Ranged' => 'ability_marksmanship', 'Spell' => 'spell_arcane_blast'][$combat];
            $summary = [
                ['Health', $format('maxhealth'), 'inv_potion_54'],
                [$resourceName, number_format((int) $stats->$resource / $divisor), $resourceIcon],
                [$groups['Base stats'][$primary], $format($primary), $primaryIcon],
                [$groups[$combat][$power], $format($power), $powerIcon],
                ['Critical chance', $format($crit), 'ability_backstab'],
                ['Armor', $format('armor'), 'inv_shield_06'],
            ];
        @endphp
        <dl class="stat-summary">
            @foreach($summary as [$label, $value, $icon])
                <div>
                    <dt><img src="{{ $iconBase.$icon }}.jpg" alt="" width="36" height="36"><span>{{ $label }}</span></dt><dd>{{ $value }}</dd>
                </div>
            @endforeach
        </dl>
    @else
        <p class="character-empty">Stats will appear after this character logs out.</p>
    @endif
    <x-character-disclosure label="Detailed stats" :divider="true">
        <div class="stat-groups">
            @foreach($stats ? [['Base stats', 'Activity'], ['Melee', 'Ranged', 'Spell'], ['Defense', 'Resistance']] : [['Activity']] as $column)
                <div class="stat-column">
                    @foreach($column as $label)
                        <section class="stat-group"><h3>{{ $label }}</h3><dl>
                            @if($label === 'Activity')
                                @foreach(['Learned spells' => $character->spells_count, 'Pets' => $character->pets_count, 'Inventory items' => $character->inventory_items_count] as $name => $value)<div><dt>{{ $name }}</dt><dd>{{ number_format($value) }}</dd></div>@endforeach
                            @else
                                @foreach($groups[$label] as $field => $name)<div><dt>{{ $name }}</dt><dd>{{ $format($field) }}</dd></div>@endforeach
                            @endif
                        </dl></section>
                    @endforeach
                </div>
            @endforeach
        </div>
    </x-character-disclosure>
</section>
