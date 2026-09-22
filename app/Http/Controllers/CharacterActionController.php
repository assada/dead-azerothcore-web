<?php

namespace App\Http\Controllers;

use App\Enums\AccountAction;
use App\Support\CharacterActions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CharacterActionController extends Controller
{
    public function store(Request $request, CharacterActions $actions, int $realmId, int $guid): RedirectResponse
    {
        $validated = $request->validateWithBag('characterAction', [
            'action' => ['required', Rule::in(['unstuck', 'rename', 'customize'])],
            'request_id' => ['required', 'uuid'],
            'confirmed' => ['accepted'],
        ]);

        $operation = $actions->run($request->user(), $realmId, $guid, AccountAction::from($validated['action']), $validated['request_id']);

        return redirect()->back(fallback: route('dashboard'))->with('character-operation', [
            'name' => $operation->character_name,
            'action' => $operation->action->label(),
            'status' => $operation->status,
            'message' => $operation->context['message'] ?? $operation->result(),
        ]);
    }
}
