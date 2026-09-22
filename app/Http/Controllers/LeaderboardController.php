<?php

namespace App\Http\Controllers;

use App\Http\Requests\TableRequest;
use App\Models\Character;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class LeaderboardController extends Controller
{
    public function __invoke(TableRequest $request): JsonResponse
    {
        [$metric, $direction] = $request->order([2 => 'money', 3 => 'totaltime', 4 => 'totalHonorPoints', 5 => 'arenaPoints'], 2, 'desc');
        $start = $request->integer('start');
        $length = $request->integer('length', 25);
        $pattern = $request->searchPattern();
        $query = Character::query()->where('name', '<>', '');
        $total = (clone $query)->count();

        // Rank before filtering so a name search keeps the character's realm position.
        $ranked = (clone $query)->select([
            'guid', 'name', 'race', 'class', 'level', 'money', 'totaltime', 'totalHonorPoints', 'arenaPoints',
        ])->selectRaw("ROW_NUMBER() OVER (ORDER BY {$metric} DESC, guid ASC) AS position");
        $rows = DB::connection('acore_characters')->query()->fromSub($ranked, 'ranked');
        if ($pattern) {
            $query->whereRaw("name LIKE ? ESCAPE '!'", [$pattern]);
            $rows->whereRaw("name LIKE ? ESCAPE '!'", [$pattern]);
        }
        $filtered = ! $pattern ? $total : $query->count();
        $characters = $rows->orderBy('position', $direction === 'desc' ? 'asc' : 'desc')
            ->offset($start)->limit($length)->get();

        return response()->json([
            'draw' => $request->integer('draw'),
            'recordsTotal' => $total,
            'recordsFiltered' => $filtered,
            'data' => $characters->map(fn ($character) => [
                'rank' => (int) $character->position,
                'character' => view('components.character-summary', ['character' => $character])->render(),
                'gold' => view('components.money', ['amount' => (int) $character->money])->render(),
                'played' => number_format(intdiv($character->totaltime, 3600)).'h '.intdiv($character->totaltime % 3600, 60).'m',
                'honor' => number_format($character->totalHonorPoints),
                'arena' => number_format($character->arenaPoints),
            ]),
        ]);
    }
}
