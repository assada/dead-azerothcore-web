<?php

namespace App\Console\Commands;

use Generator;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use RuntimeException;

class ImportGameData extends Command
{
    protected $signature = 'wow:import-data {source : Directory with client CSV files, Faction.dbc and customization metadata}';

    protected $description = 'Build the static WotLK data used by character pages';

    private string $source;

    public function handle(): int
    {
        $this->source = rtrim($this->argument('source'), '/');
        File::ensureDirectoryExists(resource_path('data/game/customization'));

        $icons = $this->index('SpellIcon_3.3.5_12340.csv', ['TextureFilename']);
        $icon = static fn ($id) => strtolower(preg_replace('/^interface\\\\(?:icons|spellbook)\\\\|\.$/i', '', $icons[$id]['TextureFilename'] ?? 'inv_misc_questionmark'));
        $spells = $this->index('Spell_3.3.5_12340.csv', ['Mechanic', 'SpellIconID', 'Name_lang[0]']);
        $items = $this->index('Item_3.3.5_12340.csv', ['ClassID', 'SubclassID', 'DisplayInfoID', 'InventoryType']);
        $retail = $this->index('Item_9.2.0_41462.csv', ['InventoryType']);
        $appearances = $this->index('ItemAppearance_9.2.0_41462.csv', ['ItemDisplayInfoID']);
        $modified = [];
        foreach ($this->csv('ItemModifiedAppearance_9.2.0_41462.csv') as $row) {
            // The existing viewer uses the first appearance for an item.
            $modified[(int) $row['ItemID']] ??= (int) $row['ItemAppearanceID'];
        }
        $itemData = [];
        foreach ($items as $id => $row) {
            $itemData[$id] = [
                'class' => (int) $row['ClassID'], 'subclass' => (int) $row['SubclassID'],
                'inventoryType' => (int) ($retail[$id]['InventoryType'] ?? $row['InventoryType']),
                'appearance' => (int) ($appearances[$modified[$id] ?? 0]['ItemDisplayInfoID'] ?? 0),
            ];
        }
        $this->write('items', $itemData);
        $displayIcons = [];
        foreach ($this->csv('ItemDisplayInfo_3.3.5_12340.csv') as $row) {
            $displayIcons[(int) $row['ID']] = strtolower($row['InventoryIcon[0]'] ?: 'inv_misc_questionmark');
        }
        $this->write('item-icons', $displayIcons);

        $enchants = [];
        foreach ($this->csv('SpellItemEnchantment_3.3.5_12340.csv') as $row) {
            $enchants[(int) $row['ID']] = [
                'item' => (int) $row['Src_itemID'],
                'gem' => (int) ($items[(int) $row['Src_itemID']]['ClassID'] ?? 0) === 3,
            ];
        }
        $this->write('enchants', $enchants);

        $mountDisplays = [];
        foreach ($this->csv('MountXDisplay_9.2.0_41462.csv') as $row) {
            $mountDisplays[(int) $row['MountID']] ??= (int) $row['CreatureDisplayInfoID'];
        }
        $mounts = [];
        foreach ($this->csv('Mount_9.2.0_41462.csv') as $row) {
            $spellId = (int) $row['SourceSpellID'];
            $spell = $spells[$spellId] ?? null;
            if ((int) ($spell['Mechanic'] ?? 0) !== 21 || ! isset($mountDisplays[(int) $row['ID']])) {
                continue;
            }
            $mounts[$spellId] ??= [
                'spell' => $spellId, 'name' => $spell['Name_lang[0]'],
                'icon' => $icon($spell['SpellIconID']), 'display' => $mountDisplays[(int) $row['ID']],
            ];
        }
        $this->write('mounts', $mounts);

        $talents = [];
        foreach ($this->csv('Talent_3.3.5_12340.csv') as $row) {
            $ranks = array_values(array_filter(array_map(fn ($i) => (int) $row["SpellRank[{$i}]"], range(0, 4))));
            $spell = $spells[$ranks[0] ?? 0] ?? [];
            $talents[(int) $row['TabID']][] = [
                'id' => (int) $row['ID'], 'row' => (int) $row['TierID'], 'column' => (int) $row['ColumnIndex'],
                'ranks' => $ranks, 'prerequisite' => (int) $row['PrereqTalent[0]'],
                'name' => $spell['Name_lang[0]'] ?? '', 'icon' => $icon($spell['SpellIconID'] ?? 0),
            ];
        }
        $trees = [];
        foreach ($this->csv('TalentTab_3.3.5_12340.csv') as $row) {
            $mask = (int) $row['ClassMask'];
            if (! $mask) {
                continue;
            }
            $class = (int) log($mask, 2) + 1;
            $trees[$class][(int) $row['OrderIndex']] = [
                'id' => (int) $row['ID'], 'name' => $row['Name_lang[0]'],
                'icon' => $icon($row['SpellIconID']), 'background' => strtolower($row['BackgroundFile']),
                'talents' => $talents[(int) $row['ID']] ?? [],
            ];
        }
        foreach ($trees as &$classTrees) {
            ksort($classTrees);
            $classTrees = array_values($classTrees);
        }
        unset($classTrees);
        $this->write('talents', $trees);

        $glyphs = [];
        foreach ($this->csv('GlyphProperties_3.3.5_12340.csv') as $row) {
            $spell = $spells[(int) $row['SpellID']] ?? [];
            $glyphs[(int) $row['ID']] = [
                'spell' => (int) $row['SpellID'], 'name' => $spell['Name_lang[0]'] ?? '',
                'icon' => $icon($spell['SpellIconID'] ?? 0), 'minor' => (int) $row['GlyphSlotFlags'] === 1,
            ];
        }
        $this->write('glyphs', $glyphs);

        $achievements = [];
        foreach ($this->csv('Achievement_3.3.5_12340.csv') as $row) {
            $achievements[(int) $row['ID']] = [
                'id' => (int) $row['ID'], 'title' => $row['Title_lang[0]'],
                'description' => $row['Description_lang[0]'], 'points' => (int) $row['Points'],
                'category' => (int) $row['Category'], 'faction' => (int) $row['Faction'],
                'icon' => $icon($row['IconID']), 'flags' => (int) $row['Flags'],
            ];
        }
        $this->write('achievements', $achievements);
        $categories = [];
        foreach ($this->csv('AchievementCategory_3.3.5_12340.csv') as $row) {
            $categories[(int) $row['ID']] = [
                'id' => (int) $row['ID'], 'name' => $row['Name_lang[0]'],
                'parent' => (int) $row['Parent'], 'order' => (int) $row['Ui_order'],
            ];
        }
        $this->write('achievement-categories', $categories);

        $skills = [];
        foreach ($this->csv('Skills.csv') as $row) {
            $skills[(int) $row['ID']] = [
                'name' => $row['Name'], 'categoryId' => (int) $row['CategoryId'],
                'icon' => $icon($row['SpellIcon']),
            ];
        }
        $this->write('skills', $skills);
        $areas = [];
        foreach ($this->csv('Areas.csv') as $row) {
            $areas[(int) $row['ID']] = ['zoneName' => $row['ZoneName'], 'mapId' => (int) $row['MapId'], 'areaId' => (int) $row['AreaId']];
        }
        $this->write('areas', $areas);
        $this->importFactions();

        foreach ([1, 2, 3, 4, 5, 6, 7, 8, 10, 11] as $race) {
            foreach ([0, 1] as $gender) {
                $path = "{$this->source}/meta/charactercustomization2/{$race}_{$gender}.json";
                $data = json_decode(file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);
                $this->write("customization/{$race}_{$gender}", $data['Options']);
            }
        }

        return self::SUCCESS;
    }

    private function importFactions(): void
    {
        $data = file_get_contents("{$this->source}/Faction.dbc");
        $header = unpack('a4magic/Vcount/Vfields/Vsize/Vstrings', substr($data, 0, 20));
        if ($header['magic'] !== 'WDBC' || $header['fields'] !== 57 || $header['size'] !== 228) {
            throw new RuntimeException('Expected a WotLK Faction.dbc');
        }
        $strings = substr($data, 20 + $header['count'] * $header['size']);
        $factions = [];
        for ($i = 0; $i < $header['count']; $i++) {
            $row = array_values(unpack('V*', substr($data, 20 + $i * 228, 228)));
            $signed = static fn ($n) => $n > 0x7FFFFFFF ? $n - 0x100000000 : $n;
            $factions[$row[0]] = [
                'name' => substr($strings, $row[23], strpos($strings, "\0", $row[23]) - $row[23]),
                'reputationId' => $signed($row[1]), 'parent' => $row[18],
                'raceMasks' => array_slice($row, 2, 4), 'classMasks' => array_slice($row, 6, 4),
                'base' => array_map($signed, array_slice($row, 10, 4)), 'flags' => array_slice($row, 14, 4),
            ];
        }
        $this->write('factions', $factions);
    }

    private function csv(string $file): Generator
    {
        $handle = fopen("{$this->source}/{$file}", 'r');
        try {
            $header = fgetcsv($handle, escape: '');
            while (($row = fgetcsv($handle, escape: '')) !== false) {
                if (count($row) === count($header)) {
                    yield array_combine($header, $row);
                }
            }
        } finally {
            fclose($handle);
        }
    }

    private function index(string $file, array $columns): array
    {
        $rows = [];
        $keys = array_fill_keys($columns, true);
        foreach ($this->csv($file) as $row) {
            $rows[(int) $row['ID']] = array_intersect_key($row, $keys);
        }

        return $rows;
    }

    private function write(string $name, array $data): void
    {
        file_put_contents(resource_path("data/game/{$name}.json"), json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)."\n");
        $this->line($name.': '.count($data));
    }
}
