<?php

namespace App\Support;

use App\Enums\AccountAction;
use App\Exceptions\WorldCommandFailed;
use App\Models\Account;
use App\Models\AccountOperation;
use App\Models\Character;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;

class CharacterActions
{
    public const ACTIONS = [AccountAction::Unstuck, AccountAction::Rename, AccountAction::Customize];

    public function __construct(private WorldConsole $console) {}

    public function availability(Account $account, int $realmId, Collection $characters): array
    {
        if ($characters->isEmpty()) {
            return [];
        }

        $soap = config("wow.realms.$realmId.soap");
        $configured = $soap && $soap['url'] && $soap['username'] && $soap['password'];

        $history = $account->operations()->where('realm_id', $realmId)
            ->whereIn('character_guid', $characters->pluck('guid'))
            ->whereIn('action', self::ACTIONS)
            ->selectRaw("character_guid, action, MAX(CASE WHEN status = 'completed' THEN updated_at END) AS completed_at, MAX(CASE WHEN status IN ('pending', 'unknown') THEN created_at END) AS unresolved_at")
            ->groupBy('character_guid', 'action')->get()
            ->keyBy(fn ($row) => $row->character_guid.':'.$row->action->value);

        $result = [];
        foreach ($characters as $character) {
            foreach (self::ACTIONS as $action) {
                $record = $history->get($character->guid.':'.$action->value);
                $availableAt = $record?->completed_at
                    ? $action->availableAfter(CarbonImmutable::parse($record->completed_at)) : null;

                $reason = match (true) {
                    ! $action->enabled() => 'This service is disabled.',
                    ! $configured => 'Character services are unavailable.',
                    (bool) $record?->unresolved_at => 'A previous request needs confirmation. Contact an administrator.',
                    $availableAt?->isFuture() ?? false => 'Available '.$availableAt->format('j M Y, H:i T'),
                    (bool) ($character->at_login & $action->loginFlag()) => 'Already requested. Finish this change in the game client.',
                    (bool) $character->online => 'Log out of the game first.',
                    $action === AccountAction::Unstuck && ! $character->homebind => 'This character has no home location.',
                    default => null,
                };

                $result[$character->guid][$action->value] = [
                    'disabled' => $reason !== null,
                    'reason' => $reason,
                    'available_at' => $availableAt?->toIso8601String(),
                ];
            }
        }

        return $result;
    }

    public function run(Account $account, int $realmId, int $guid, AccountAction $action, string $requestId): AccountOperation
    {
        // The site currently reads one realm. Do not dispatch a command to another server.
        abort_unless($realmId === Wow::defaultRealmId(), 404);
        abort_unless(in_array($action, self::ACTIONS, true), 404);
        abort_unless($action->enabled(), 403);

        $operation = Cache::lock("character-action:$realmId:$guid", 15)->get(function () use ($account, $realmId, $guid, $action, $requestId) {
            $character = Character::query()->where('account', $account->id)->where('name', '<>', '')
                ->with('homebind')->findOrFail($guid);

            $existing = AccountOperation::find($requestId);
            if ($existing) {
                abort_unless((int) $existing->account_id === (int) $account->id
                    && (int) $existing->realm_id === $realmId && (int) $existing->character_guid === $guid
                    && $existing->action === $action, 409);

                return $existing;
            }

            $availability = $this->availability($account, $realmId, collect([$character]))[$guid][$action->value];
            if ($availability['disabled']) {
                throw ValidationException::withMessages(['character' => $availability['reason']])->errorBag('characterAction');
            }

            $operation = $account->operations()->create([
                'id' => $requestId, 'realm_id' => $realmId, 'character_guid' => $guid,
                'character_name' => $character->name, 'action' => $action, 'status' => 'pending',
                'context' => $action === AccountAction::Unstuck
                    ? ['destination' => Wow::areaName((int) $character->homebind->zoneId)] : null,
            ]);

            try {
                $this->console->execute($realmId, $action->command($guid));
                $operation->update(['status' => 'completed']);
            } catch (WorldCommandFailed $error) {
                $operation->update([
                    'status' => $error->uncertain ? 'unknown' : 'failed',
                    'context' => [...($operation->context ?? []), 'message' => $error->getMessage()],
                ]);
            }

            return $operation;
        });

        if (! $operation) {
            throw ValidationException::withMessages(['character' => 'Another action is in progress. Please wait.'])->errorBag('characterAction');
        }

        return $operation;
    }
}
