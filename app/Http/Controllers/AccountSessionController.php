<?php

namespace App\Http\Controllers;

use App\Enums\AccountAction;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AccountSessionController extends Controller
{
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('endSessions', ['current_password' => ['required', 'string', new \App\Rules\CurrentAccountPassword]]);

        DB::connection(config('session.connection'))->transaction(function () use ($request) {
            Auth::getProvider()->updateRememberToken($request->user(), Str::random(60));
            $count = DB::connection(config('session.connection'))->table(config('session.table'))
                ->where('user_id', $request->user()->id)->where('id', '<>', $request->session()->getId())->delete();
            if ($count) {
                $request->user()->operations()->create(['action' => AccountAction::Sessions, 'context' => ['count' => $count]]);
            }
        });

        if ($request->cookies->has(Auth::guard()->getRecallerName())) {
            Auth::login($request->user(), true);
        }

        return back()->with('status', 'sessions-ended');
    }
}
