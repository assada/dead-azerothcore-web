<?php

namespace App\Support;

class Wow
{
    public static function factionByRace(int $race): string
    {
        $alliance = [1, 3, 4, 7, 11];
        $horde = [2, 5, 6, 8, 10];
        if (in_array($race, $alliance, true)) {
            return 'alliance';
        }
        if (in_array($race, $horde, true)) {
            return 'horde';
        }

        return 'alliance';
    }

    public static function classColors(): array
    {
        return [
            1 => '#C79C6E',
            2 => '#F58CBA',
            3 => '#ABD473',
            4 => '#FFF569',
            5 => '#FFFFFF',
            6 => '#C41E3A',
            7 => '#0070DE',
            8 => '#69CCF0',
            9 => '#9482C9',
            11 => '#FF7D0A',
        ];
    }

    public static function classNames(): array
    {
        return [
            1 => 'Warrior',
            2 => 'Paladin',
            3 => 'Hunter',
            4 => 'Rogue',
            5 => 'Priest',
            6 => 'Death Knight',
            7 => 'Shaman',
            8 => 'Mage',
            9 => 'Warlock',
            11 => 'Druid',
        ];
    }

    public static function raceNames(): array
    {
        return [
            1 => 'Human',
            2 => 'Orc',
            3 => 'Dwarf',
            4 => 'Night Elf',
            5 => 'Undead',
            6 => 'Tauren',
            7 => 'Gnome',
            8 => 'Troll',
            10 => 'Blood Elf',
            11 => 'Draenei',
        ];
    }

    public static function formatMoney(int $copper): array
    {
        $gold = intdiv($copper, 10000);
        $silver = intdiv($copper % 10000, 100);
        $copper = $copper % 100;

        return [$gold, $silver, $copper];
    }

    public static function itemIcons(): array
    {
        return app(GameData::class)->table('item-icons');
    }

    public static function formatPlayed(int $seconds): array
    {
        $hours = intdiv($seconds, 3600);
        $minutes = intdiv($seconds % 3600, 60);

        return [$hours, $minutes];
    }

    public static function realmName(int|string $realmId): string
    {
        $realmId = (int) $realmId;
        $realms = config('wow.realms', []);

        return $realms[$realmId]['name'] ?? (string) $realmId;
    }

    public static function defaultRealmId(): int
    {
        return (int) config('wow.default_realm_id', 1);
    }

    public static function realmParamFromId(int $realmId): string
    {
        return self::realmName($realmId);
    }

    public static function realmIdFromParam(string $param): int
    {
        if (ctype_digit($param)) {
            return (int) $param;
        }
        $realms = config('wow.realms', []);
        foreach ($realms as $id => $meta) {
            if (isset($meta['name']) && strcasecmp($meta['name'], $param) === 0) {
                return (int) $id;
            }
        }

        return self::defaultRealmId();
    }

    public static function achievementMetaMap(): array
    {
        return app(GameData::class)->table('achievements');
    }

    public static function achievementMeta(int $id): ?array
    {
        $map = self::achievementMetaMap();

        return $map[$id] ?? null;
    }

    public static function achievementName(int $id): ?string
    {
        $meta = self::achievementMeta($id);

        return $meta['title'] ?? null;
    }

    public static function talentSpellIds(): array
    {
        $ids = [];
        foreach (app(GameData::class)->table('talents') as $trees) {
            foreach ($trees as $tree) {
                foreach ($tree['talents'] as $talent) {
                    array_push($ids, ...$talent['ranks']);
                }
            }
        }

        return array_values(array_unique($ids));
    }

    public static function factionMap(): array
    {
        return app(GameData::class)->table('factions');
    }

    public static function factionName(int $factionId): ?string
    {
        $map = self::factionMap();

        return $map[$factionId]['name'] ?? null;
    }

    public static function reputationBounds(int $value): array
    {
        return match (true) {
            $value < -6000 => ['label' => 'Hated', 'min' => -42000, 'max' => -6000],
            $value < -3000 => ['label' => 'Hostile', 'min' => -6000, 'max' => -3000],
            $value < 0 => ['label' => 'Unfriendly', 'min' => -3000, 'max' => 0],
            $value < 3000 => ['label' => 'Neutral', 'min' => 0, 'max' => 3000],
            $value < 9000 => ['label' => 'Friendly', 'min' => 3000, 'max' => 9000],
            $value < 21000 => ['label' => 'Honored', 'min' => 9000, 'max' => 21000],
            $value < 42000 => ['label' => 'Revered', 'min' => 21000, 'max' => 42000],
            default => ['label' => 'Exalted', 'min' => 42000, 'max' => 43000],
        };
    }

    public static function reputationColor(string $standing): string
    {
        return match ($standing) {
            'Hated' => '#ef4444',
            'Hostile' => '#dc2626',
            'Unfriendly' => '#f97316',
            'Neutral' => '#f59e0b',
            'Friendly' => '#22c55e',
            'Honored' => '#16a34a',
            'Revered' => '#0284c7',
            default => '#8b5cf6',
        };
    }

    public static function expansionIdFromFaction(int $factionId): int
    {
        $factions = self::factionMap();
        while (isset($factions[$factionId])) {
            if ($factionId === 1097) {
                return 2;
            }
            if ($factionId === 980) {
                return 1;
            }
            $factionId = $factions[$factionId]['parent'];
        }

        return 0;
    }

    public static function areaMap(): array
    {
        return app(GameData::class)->table('areas');
    }

    public static function areaName(int $id): ?string
    {
        $map = self::areaMap();
        $name = $map[$id]['zoneName'] ?? null;
        if (is_string($name) && $name !== '') {
            return $name;
        }

        return $id > 0 ? ('Zone '.$id) : null;
    }

    public static function skillMap(): array
    {
        return app(GameData::class)->table('skills');
    }

    public static function skillName(int $id): ?string
    {
        $map = self::skillMap();

        return $map[$id]['name'] ?? null;
    }

    public static function skillCategoryId(int $id): ?int
    {
        $map = self::skillMap();

        return isset($map[$id]) ? (int) $map[$id]['categoryId'] : null;
    }
}
