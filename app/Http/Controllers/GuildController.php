<?php

namespace App\Http\Controllers;

use App\Http\Requests\TableRequest;
use App\Models\Character;
use App\Models\Guild;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class GuildController extends Controller
{
    public function index(TableRequest $request): View|JsonResponse
    {
        if (! $request->expectsJson()) {
            return view('guilds.index');
        }
        [$column, $direction] = $request->order([0 => 'name', 2 => 'members_count'], 0);
        $query = Guild::query();
        $total = (clone $query)->count();
        if ($pattern = $request->searchPattern()) {
            $query->whereRaw("name LIKE ? ESCAPE '!'", [$pattern]);
        }
        $filtered = (clone $query)->count();
        $guilds = $query->select(['guildid', 'name', 'leaderguid'])->withCount('members')
            ->with('leader:guid,name,race,class,level')->orderBy($column, $direction)->orderBy('guildid')
            ->offset($request->integer('start'))->limit($request->integer('length', 25))->get();

        return response()->json([
            'draw' => $request->integer('draw'), 'recordsTotal' => $total, 'recordsFiltered' => $filtered,
            'data' => $guilds->map(fn ($guild) => [
                'guild' => view('components.guild-link', ['guild' => $guild])->render(),
                'leader' => $guild->leader ? view('components.character-summary', ['character' => $guild->leader])->render() : '—',
                'members' => number_format($guild->members_count),
            ]),
        ]);
    }

    public function show(TableRequest $request, int $guildid): View|JsonResponse
    {
        $guild = Guild::query()->select(['guildid', 'name', 'leaderguid'])->findOrFail($guildid);
        $query = Character::query()->join('guild_member', 'guild_member.guid', '=', 'characters.guid')
            ->where('guild_member.guildid', $guildid)->where('characters.name', '<>', '');
        $total = (clone $query)->count();
        if (! $request->expectsJson()) {
            return view('guilds.show', [
                'guild' => $guild, 'leader' => $guild->leader,
                'memberCount' => $total, 'onlineCount' => (clone $query)->where('online', 1)->count(),
            ]);
        }
        [$column, $direction] = $request->order([0 => 'characters.name', 1 => 'guild_member.rank', 2 => 'level', 3 => 'totaltime'], 1);
        if ($pattern = $request->searchPattern()) {
            $query->whereRaw("characters.name LIKE ? ESCAPE '!'", [$pattern]);
        }
        $filtered = (clone $query)->count();
        $members = $query->leftJoin('guild_rank', function ($join) {
            $join->on('guild_rank.guildid', '=', 'guild_member.guildid')->on('guild_rank.rid', '=', 'guild_member.rank');
        })->select(['characters.guid', 'characters.name', 'race', 'class', 'level', 'online', 'totaltime', 'guild_rank.rname'])
            ->orderBy($column, $direction)->orderBy('characters.guid')
            ->offset($request->integer('start'))->limit($request->integer('length', 25))->get();

        return response()->json([
            'draw' => $request->integer('draw'), 'recordsTotal' => $total, 'recordsFiltered' => $filtered,
            'data' => $members->map(fn ($character) => [
                'character' => view('components.character-summary', ['character' => $character, 'showOnline' => true])->render(),
                'rank' => e($character->rname ?? '—'), 'level' => $character->level,
                'played' => number_format(intdiv($character->totaltime, 3600)).'h '.intdiv($character->totaltime % 3600, 60).'m',
            ]),
        ]);
    }
}
