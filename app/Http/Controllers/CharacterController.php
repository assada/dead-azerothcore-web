<?php

namespace App\Http\Controllers;

use App\Http\Requests\TableRequest;
use App\Models\Character;
use App\Support\CharacterActions;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CharacterController extends Controller
{
    public function index(TableRequest $request, string $realm): View|JsonResponse
    {
        $realmId = \App\Support\Wow::realmIdFromParam($realm);
        if (! $request->expectsJson()) {
            return view('characters.index', [
                'realm' => \App\Support\Wow::realmName($realmId), 'realmId' => $realmId,
                'classes' => \App\Support\Wow::classNames(),
            ]);
        }
        $filters = $request->validate([
            'class' => 'nullable|integer|in:1,2,3,4,5,6,7,8,9,11',
            'guild' => 'nullable|string|max:100',
            'level_min' => 'nullable|integer|between:1,80',
            'level_max' => 'nullable|integer|between:1,80'.($request->filled('level_min') ? '|gte:level_min' : ''),
        ]);
        [$column, $direction] = $request->order([0 => 'name', 2 => 'level', 3 => 'totaltime', 4 => 'totalHonorPoints', 5 => 'money'], 2, 'desc');
        $query = Character::query()->where('name', '<>', '');
        $total = (clone $query)->count();
        if ($pattern = $request->searchPattern()) {
            $query->whereRaw("name LIKE ? ESCAPE '!'", [$pattern]);
        }
        if ($filters['class'] ?? null) {
            $query->where('class', $filters['class']);
        }
        if ($guild = trim($filters['guild'] ?? '')) {
            $query->whereHas('guild.guild', fn ($q) => $q->where('name', $guild));
        }
        foreach (['level_min' => '>=', 'level_max' => '<='] as $key => $operator) {
            if ($filters[$key] ?? null) {
                $query->where('level', $operator, $filters[$key]);
            }
        }
        $filtered = (clone $query)->count();
        $characters = $query->select(['guid', 'name', 'race', 'class', 'level', 'totaltime', 'money', 'totalHonorPoints'])
            ->with(['guild:guid,guildid', 'guild.guild:guildid,name'])->orderBy($column, $direction)->orderBy('guid')
            ->offset($request->integer('start'))->limit($request->integer('length', 25))->get();

        return response()->json([
            'draw' => $request->integer('draw'), 'recordsTotal' => $total, 'recordsFiltered' => $filtered,
            'data' => $characters->map(fn ($character) => [
                'character' => view('components.character-summary', ['character' => $character, 'realmId' => $realmId])->render(),
                'guild' => view('components.guild-link', ['guild' => $character->guild?->guild])->render(),
                'level' => $character->level,
                'played' => number_format(intdiv($character->totaltime, 3600)).'h '.intdiv($character->totaltime % 3600, 60).'m',
                'honor' => number_format($character->totalHonorPoints),
                'gold' => view('components.money', ['amount' => (int) $character->money])->render(),
            ]),
        ]);
    }

    public function show(Request $request, CharacterActions $actions, string $realm, string $name): View
    {
        $input = $request->validate([
            'tab' => 'sometimes|in:equipment,talents,mounts,achievements,pvp',
            'spec' => 'sometimes|integer|between:0,1', 'q' => 'nullable|string|max:100',
            'category' => 'nullable|integer|min:1', 'status' => 'sometimes|in:all,earned,missing',
            'page' => 'sometimes|integer|min:1',
        ]);
        $tab = $input['tab'] ?? 'equipment';
        $realmId = \App\Support\Wow::realmIdFromParam($realm);
        $realm = \App\Support\Wow::realmName($realmId);
        $relations = ['guild.guild', 'achievements'];
        if ($tab === 'equipment') {
            array_push($relations, 'stats', 'skills', 'reputations');
        } elseif ($tab === 'talents') {
            array_push($relations, 'talents', 'glyphs');
        }
        $character = Character::query()->where('name', $name)->with($relations)
            ->withCount(['pets', 'spells', 'inventoryItems', 'rewardedQuests as completed_quests_count'])
            ->firstOrFail();
        $data = app(\App\Support\GameData::class);
        $gear = app(\App\Support\Equipment::class);
        $equipment = $gear->forCharacter($character);
        $achievements = app(\App\Support\CharacterAchievements::class)->forCharacter($character);
        $mountIds = $character->spells()
            ->whereIn('spell', array_keys($data->table('mounts')))->pluck('spell')->all();
        $mounts = collect($data->table('mounts'))->only($mountIds)->sortBy('name');
        $view = [
            'character' => $character, 'realm' => $realm, 'realmId' => $realmId, 'tab' => $tab,
            'actions' => [],
            'equipment' => collect($equipment)->keyBy('slot'),
            'gearScore' => \App\Support\GearScore::total($equipment, $character->class),
            'itemLevel' => \App\Support\GearScore::averageItemLevel($equipment),
            'achievementPoints' => $achievements->where('date', '>', 0)->sum('points'),
            'achievementCount' => $achievements->where('date', '>', 0)->count(),
            'mountCount' => $mounts->count(), 'tooltipUrl' => rtrim(config('wow.tooltip_url'), '/'),
        ];
        if ($request->user() && (int) $character->account === (int) $request->user()->id) {
            $character->load('homebind');
            $view['actions'] = $actions->availability($request->user(), $realmId, collect([$character]))[$character->guid];
        }
        if (in_array($tab, ['equipment', 'mounts'], true)) {
            $view['model'] = [
                'race' => $character->race, 'gender' => $character->gender, 'class' => $character->class,
                'options' => app(\App\Support\CharacterAppearance::class)->options($character),
                'items' => array_values(array_filter($gear->modelItems($equipment, $character->class), fn ($item) => ! (($item[0] === 1 && ($character->playerFlags & 0x0400)) || ($item[0] === 16 && ($character->playerFlags & 0x0800))))),
                'contentPath' => rtrim(asset(config('wow.modelviewer_path')), '/').'/',
            ];
        }
        if ($tab === 'equipment') {
            $skillMeta = $data->table('skills');
            $skills = $character->skills->map(function ($row) use ($skillMeta) {
                $meta = $skillMeta[$row->skill] ?? null;

                return $meta ? $meta + ['id' => (int) $row->skill, 'value' => (int) $row->value, 'max' => (int) $row->max] : null;
            })->filter()->reject(fn ($row) => in_array($row['name'], ['Internal', 'Mounts', 'Companions'], true))->sortBy('name');
            $view['skills'] = [];
            foreach ([11 => 'Professions', 9 => 'Secondary skills', 6 => 'Weapons', 7 => 'Class skills', 8 => 'Armor', 10 => 'Languages'] as $id => $label) {
                $view['skills'][$label] = $skills->where('categoryId', $id);
            }
            $view['reputations'] = \App\Support\Reputation::forCharacter($character);
            $view['recentAchievements'] = $achievements->where('date', '>', 0)->sortByDesc('date')->take(3);
        } elseif ($tab === 'talents') {
            $view['specs'] = app(\App\Support\CharacterTalents::class)->forCharacter($character);
            $view['spec'] = min((int) ($input['spec'] ?? $character->activeTalentGroup), count($view['specs']) - 1);
        } elseif ($tab === 'mounts') {
            $view['search'] = $input['q'] ?? '';
            $view['mounts'] = $mounts->filter(fn ($row) => str_contains(mb_strtolower($row['name']), mb_strtolower($view['search'])));
        } elseif ($tab === 'achievements') {
            $catalog = app(\App\Support\CharacterAchievements::class);
            $view['categories'] = $catalog->categories();
            $view['filters'] = ['q' => $input['q'] ?? '', 'category' => isset($input['category']) ? (int) $input['category'] : null, 'status' => $input['status'] ?? 'earned'];
            $filtered = $achievements->filter(function ($row) use ($view, $catalog) {
                $filters = $view['filters'];

                return str_contains(mb_strtolower($row['title']), mb_strtolower($filters['q']))
                    && (! $filters['category'] || $catalog->inCategory($row['category'], $filters['category']))
                    && ($filters['status'] === 'all' || ($filters['status'] === 'earned' ? $row['date'] > 0 : $row['date'] === 0));
            })->sortBy([['date', 'desc'], ['title', 'asc']])->values();
            $page = $input['page'] ?? 1;
            $view['achievements'] = new \Illuminate\Pagination\LengthAwarePaginator($filtered->forPage($page, 18), $filtered->count(), 18, $page, ['path' => $request->url(), 'query' => $request->query()]);
            $view['achievementTotal'] = $achievements->count();
        } elseif ($tab === 'pvp') {
            $view['arenaTeams'] = $character->arenaTeams()->orderBy('type')->get();
            $view['arenaRating'] = \Illuminate\Support\Facades\DB::connection('acore_characters')->table('character_arena_stats')->where('guid', $character->guid)->max('matchMakerRating');
        }

        return view('characters.show', $view);
    }
}
