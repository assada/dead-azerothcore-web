<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Notifications\ConfirmEmailChange;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\View\View;
use UAParser\Parser;

class ProfileController extends Controller
{
    public function edit(Request $request): View
    {
        $account = $request->user();
        $parser = Parser::create();
        $sessions = DB::connection(config('session.connection'))->table(config('session.table'))
            ->where('user_id', $account->id)
            ->where('last_activity', '>=', now()->subMinutes(config('session.lifetime'))->timestamp)
            ->orderByDesc('last_activity')->get(['id', 'ip_address', 'user_agent', 'last_activity'])
            ->map(function ($session) use ($parser) {
                $client = $parser->parse(substr((string) $session->user_agent, 0, 2048));
                $session->browser = $client->ua->family === 'Other' ? 'Unknown browser' : $client->ua->family;
                $session->platform = $client->os->family === 'Other' ? 'Unknown device' : $client->os->family;

                return $session;
            });

        return view('profile.edit', [
            'user' => $account,
            'sessions' => $sessions,
            'ban' => $account->getConnection()->table('account_banned')->where('id', $account->id)
                ->where('active', 1)->where(fn ($query) => $query->whereColumn('unbandate', 'bandate')->orWhere('unbandate', '>', now()->timestamp))
                ->orderByDesc('bandate')->first(),
        ]);
    }

    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $account = $request->user();
        $email = $request->validated('email');
        if ($email === $account->email && $email === $account->username) {
            return back()->with('status', 'profile-updated');
        }
        $account->profile()->updateOrCreate([], ['pending_email' => $email]);
        Notification::route('mail', $email)->notify(new ConfirmEmailChange($email, $account->id));

        return back()->with('status', 'email-confirmation-sent');
    }
}
