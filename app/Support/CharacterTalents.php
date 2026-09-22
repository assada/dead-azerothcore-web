<?php

namespace App\Support;

use App\Models\Character;

class CharacterTalents
{
    public function __construct(private GameData $data) {}

    public function forCharacter(Character $character): array
    {
        $trees = $this->data->table('talents')[$character->class] ?? [];
        $glyphMeta = $this->data->table('glyphs');
        $specs = [];
        for ($spec = 0; $spec < max(1, min(2, $character->talentGroupsCount)); $spec++) {
            $spells = $character->talents->filter(fn ($row) => (int) $row->specMask & (1 << $spec))->pluck('spell')->flip();
            $specTrees = $trees;
            foreach ($specTrees as &$tree) {
                $tree['points'] = 0;
                foreach ($tree['talents'] as &$talent) {
                    $talent['rank'] = 0;
                    foreach ($talent['ranks'] as $rank => $spell) {
                        if ($spells->has($spell)) {
                            $talent['rank'] = $rank + 1;
                        }
                    }
                    $talent['spell'] = $talent['ranks'][max(0, $talent['rank'] - 1)];
                    $tree['points'] += $talent['rank'];
                }
                unset($talent);
            }
            unset($tree);
            $glyphs = ['Major' => [], 'Minor' => []];
            $row = $character->glyphs->firstWhere('talentGroup', $spec);
            for ($slot = 1; $row && $slot <= 6; $slot++) {
                $meta = $glyphMeta[$row->{'glyph'.$slot}] ?? null;
                if ($meta) {
                    $glyphs[$meta['minor'] ? 'Minor' : 'Major'][] = $meta;
                }
            }
            $specs[] = ['trees' => $specTrees, 'glyphs' => $glyphs, 'active' => $spec === $character->activeTalentGroup];
        }

        return $specs;
    }
}
