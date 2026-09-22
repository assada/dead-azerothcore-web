<?php

namespace App\Support;

use App\Models\Character;
use Illuminate\Support\Collection;

class CharacterAchievements
{
    public function __construct(private GameData $data) {}

    public function forCharacter(Character $character): Collection
    {
        $earned = $character->achievements->pluck('date', 'achievement');
        $faction = Wow::factionByRace($character->race) === 'horde' ? 0 : 1;

        return collect($this->data->table('achievements'))
            ->filter(fn ($row) => ($row['faction'] === -1 || $row['faction'] === $faction)
                && ! ($row['flags'] & 3))
            ->map(fn ($row) => $row + ['date' => (int) ($earned[$row['id']] ?? 0)]);
    }

    public function categories(): array
    {
        $categories = $this->data->table('achievement-categories');
        $result = [];
        $add = function (int $parent, int $depth) use (&$add, &$result, $categories): void {
            $children = array_filter($categories, fn ($row) => $row['parent'] === $parent && $row['id'] !== 1);
            uasort($children, fn ($a, $b) => $a['order'] <=> $b['order']);
            foreach ($children as $row) {
                $result[$row['id']] = str_repeat('— ', $depth).$row['name'];
                $add($row['id'], $depth + 1);
            }
        };
        $add(-1, 0);

        return $result;
    }

    public function inCategory(int $id, int $category): bool
    {
        $categories = $this->data->table('achievement-categories');
        while (isset($categories[$id])) {
            if ($id === $category) {
                return true;
            }
            $id = $categories[$id]['parent'];
        }

        return false;
    }
}
