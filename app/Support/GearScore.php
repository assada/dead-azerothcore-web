<?php

namespace App\Support;

class GearScore
{
    private const SLOT_MOD = [1, .5625, .75, 0, 1, .75, 1, .75, .5625, .75, .5625, .5625, .5625, .5625, .5625, 1, 1, .3164, 0];

    private const ENCHANTABLE = [0, 2, 4, 6, 7, 8, 9, 14, 15, 16, 17];

    private const TWO_HAND = [1, 5, 6, 8, 10];

    public static function total(array $equipment, int $class): int
    {
        $slots = array_column($equipment, null, 'slot');
        $dualTwoHand = isset($slots[15], $slots[16]) && (self::twoHand($slots[15]) || self::twoHand($slots[16]));
        $total = 0;
        foreach ($equipment as $item) {
            $slot = $item['slot'];
            $mod = self::SLOT_MOD[$slot] ?? 0;
            if (! $mod) {
                continue;
            }
            $quality = $item['quality'];
            $level = $item['itemLevel'];
            $scale = 1;
            if ($quality === 5) {
                $quality = 4;
                $scale = 1.3;
            } elseif ($quality === 7) {
                $quality = 3;
                $level = 187.05;
            } elseif ($quality <= 1) {
                $quality = 2;
                $scale = .005;
            }
            $formula = $level > 120
                ? [4 => [91.45, .65], 3 => [81.375, .8125], 2 => [73, 1]]
                : [4 => [26, 1.2], 3 => [.75, 1.8], 2 => [8, 2], 1 => [0, 2.25]];
            [$a, $b] = $formula[$quality] ?? $formula[2];
            $score = (($level - $a) / $b) * $mod * 1.8618 * $scale;
            if ($slot === 15 && self::twoHand($item)) {
                $score *= 2;
            }
            if ($class === 3) {
                if ($slot === 15 || $slot === 16) {
                    $score *= .3164;
                } elseif ($slot === 17) {
                    $score *= 5.3224;
                }
            }
            if ($dualTwoHand && ($slot === 15 || $slot === 16)) {
                $score *= .5;
            }
            $score = max(0, floor($score));
            $thrown = $slot === 17 && $item['classId'] === 2 && $item['subclassId'] === 16;
            if (! $thrown && in_array($slot, self::ENCHANTABLE, true) && ! preg_match('/\b[1-9]\d*\b/', $item['enchantments'])) {
                $score *= 1 + (floor(-2 * $mod * 100) / 100) / 100;
            }
            $total += max(0, (int) floor($score));
        }

        return $total;
    }

    public static function averageItemLevel(array $equipment): int
    {
        $slots = array_column($equipment, null, 'slot');
        $levels = [];
        foreach (range(0, 17) as $slot) {
            if ($slot !== 3 && $slot !== 16 && isset($slots[$slot])) {
                $levels[] = $slots[$slot]['itemLevel'];
            }
        }
        if (isset($slots[16])) {
            $levels[] = $slots[16]['itemLevel'];
        } elseif (isset($slots[15]) && self::twoHand($slots[15])) {
            $levels[] = $slots[15]['itemLevel'];
        }

        return $levels ? (int) floor(array_sum($levels) / count($levels)) : 0;
    }

    private static function twoHand(array $item): bool
    {
        return $item['classId'] === 2 && in_array($item['subclassId'], self::TWO_HAND, true);
    }
}
