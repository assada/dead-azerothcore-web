<?php

namespace App\Http\Controllers;

use App\Models\Character;
use App\Support\CharacterActions;
use App\Support\Wow;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(CharacterActions $actions): View
    {
        $realmId = Wow::defaultRealmId();
        $characters = Character::query()->select(['guid', 'name', 'race', 'class', 'level', 'totaltime', 'money', 'totalHonorPoints', 'online', 'at_login'])
            ->with(['guild:guid,guildid', 'guild.guild:guildid,name', 'homebind:guid,zoneId'])
            ->where('account', Auth::id())->where('name', '<>', '')->orderBy('name')->get();

        return view('dashboard', [
            'characters' => $characters,
            'realmId' => $realmId,
            'actions' => $actions->availability(Auth::user(), $realmId, $characters),
        ]);
    }
}
