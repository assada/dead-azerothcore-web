<?php

namespace App\Support;

use App\Models\Character;

class Reputation
{
    public static function base(array $faction, int $race, int $class): int
    {
        foreach ($faction['raceMasks'] as $i => $mask) {
            $classMask = $faction['classMasks'][$i];
            if (($mask & (1 << ($race - 1)) || ($mask === 0 && $classMask !== 0))
                && ($classMask & (1 << ($class - 1)) || $classMask === 0)) {
                return $faction['base'][$i];
            }
        }

        return 0;
    }

    public static function forCharacter(Character $character): array
    {
        $factions = Wow::factionMap();
        $groups = ['Wrath of the Lich King' => [], 'The Burning Crusade' => [], 'Classic' => []];
        $expansions = [2 => 'Wrath of the Lich King', 1 => 'The Burning Crusade', 0 => 'Classic'];
        foreach ($character->reputations as $row) {
            $meta = $factions[$row->faction] ?? null;
            // Only visible reputation bars; DBC headers are not factions to earn reputation with.
            if (! $meta || $meta['reputationId'] < 0 || ! ((int) $row->flags & 1) || ((int) $row->flags & 12)
                || in_array((int) $row->faction, [1118, 980, 1097, 1117], true)) {
                continue;
            }
            $value = (int) $row->standing + self::base($meta, $character->race, $character->class);
            $bounds = Wow::reputationBounds($value);
            $progress = max(0, min($bounds['max'] - $bounds['min'], $value - $bounds['min']));
            $groups[$expansions[Wow::expansionIdFromFaction((int) $row->faction)]][] = [
                'name' => $meta['name'], 'standing' => $bounds['label'], 'value' => $progress,
                'max' => $bounds['max'] - $bounds['min'], 'color' => Wow::reputationColor($bounds['label']),
            ];
        }
        foreach ($groups as &$rows) {
            usort($rows, fn ($a, $b) => strcasecmp($a['name'], $b['name']));
        }

        return $groups;
    }
}
