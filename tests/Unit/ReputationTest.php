<?php

use App\Support\Reputation;
use App\Support\Wow;

it('uses the Wrath standing thresholds', function (int $value, string $rank) {
    expect(Wow::reputationBounds($value)['label'])->toBe($rank);
})->with([
    [-42000, 'Hated'], [-6000, 'Hostile'], [-3000, 'Unfriendly'], [0, 'Neutral'],
    [2999, 'Neutral'], [3000, 'Friendly'], [8999, 'Friendly'], [9000, 'Honored'],
    [20999, 'Honored'], [21000, 'Revered'], [41999, 'Revered'], [42000, 'Exalted'],
]);

it('selects the race and class base reputation', function () {
    $faction = ['raceMasks' => [1, 2, 0, 0], 'classMasks' => [0, 0, 32, 0], 'base' => [3000, -42000, -6000, 0]];
    expect(Reputation::base($faction, 1, 1))->toBe(3000)
        ->and(Reputation::base($faction, 2, 1))->toBe(-42000)
        ->and(Reputation::base($faction, 5, 6))->toBe(-6000)
        ->and(Reputation::base($faction, 5, 1))->toBe(0);
});
