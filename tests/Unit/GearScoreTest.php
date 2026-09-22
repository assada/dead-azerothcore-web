<?php

use App\Support\GearScore;

it('preserves the server GearScore and item level formulas', function (array $fixture) {
    expect(GearScore::total($fixture['equipment'], $fixture['class']))->toBe($fixture['score'])
        ->and(GearScore::averageItemLevel($fixture['equipment']))->toBe($fixture['level']);
})->with(function () {
    foreach (json_decode(file_get_contents(__DIR__.'/../Fixtures/gearscore.json'), true) as $fixture) {
        yield $fixture['name'] => [$fixture];
    }
});
