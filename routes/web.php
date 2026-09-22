<?php

use App\Http\Controllers\AccountOperationController;
use App\Http\Controllers\AccountSessionController;
use App\Http\Controllers\AuctionController;
use App\Http\Controllers\CharacterActionController;
use App\Http\Controllers\CharacterController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GuildController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    $serverName = \App\Support\Wow::realmName(\App\Support\Wow::defaultRealmId());
    $expansion = config('wow.expansion');
    $rates = config('wow.rates', []);
    $realmlist = config('wow.realmlist');
    $downloadUrl = config('wow.client_download_url');

    $status = false;
    try {
        \Illuminate\Support\Facades\DB::connection('acore_characters')->getPdo();
        $status = true;
    } catch (\Throwable $e) {
        $status = false;
    }

    $onlineCount = \App\Models\Character::query()->where('online', 1)->count();

    return view('welcome', compact(
        'serverName', 'expansion', 'rates', 'realmlist', 'downloadUrl', 'status', 'onlineCount'
    ));
})->name('home');

Route::get('/dashboard', [DashboardController::class, 'index'])->middleware(['auth'])->name('dashboard');

Route::middleware(config('wow.community_public') ? [] : ['auth'])->group(function () {
    Route::get('/leaderboard', \App\Http\Controllers\LeaderboardController::class)->name('leaderboard');
    Route::get('/auctions', [AuctionController::class, 'index'])->name('auctions.index');
    Route::get('/characters/{realm}', [CharacterController::class, 'index'])->name('characters.index');
    Route::get('/characters/{realm}/{name}', [CharacterController::class, 'show'])->name('characters.show');
    Route::get('/guilds', [GuildController::class, 'index'])->name('guilds.index');
    Route::get('/guilds/{guildid}', [GuildController::class, 'show'])->name('guilds.show');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [\App\Http\Controllers\AccountDeactivationController::class, 'destroy'])->middleware('throttle:5,1')->name('profile.destroy');
    Route::get('/profile/email/{id}/{hash}', [\App\Http\Controllers\AccountEmailController::class, 'show'])->middleware('signed')->name('profile.email.confirm');
    Route::post('/profile/email/{id}/{hash}', [\App\Http\Controllers\AccountEmailController::class, 'update'])->middleware(['signed', 'throttle:5,1']);
    Route::get('/profile/history', [AccountOperationController::class, 'index'])->name('profile.history');
    Route::delete('/profile/sessions', [AccountSessionController::class, 'destroy'])->middleware('throttle:5,1')->name('profile.sessions.destroy');
    Route::post('/realms/{realmId}/characters/{guid}/actions', [CharacterActionController::class, 'store'])
        ->whereNumber(['realmId', 'guid'])->middleware('throttle:10,1')->name('characters.actions.store');
});

require __DIR__.'/auth.php';
