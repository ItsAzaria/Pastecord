<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Http\Controllers\Auth\DiscordController;
use App\Http\Controllers\PasteController;
use App\Models\Paste;

Route::get('/', function () {
    return Inertia::render('Index', [
    ]);
})->name('home');

Route::post('pastes', [PasteController::class, 'store'])->name('pastes.store');
Route::delete('pastes/{key}', [PasteController::class, 'destroy'])->middleware(['auth'])->name('pastes.destroy');
Route::get('{key}', [PasteController::class, 'show'])
    ->where('key', '[A-Za-z0-9]{32}')
    ->name('pastes.show');

Route::get('dashboard', function (Request $request) {
    $user = $request->user();

    $pastesQuery = Paste::query()->latest();

    if ($user?->discord_id) {
        $pastesQuery->where('discord_id', $user->discord_id);
    } else {
        $pastesQuery->whereRaw('1 = 0');
    }

    $pastes = $pastesQuery
        ->select([
            'id',
            'key',
            'language',
            'encrypted',
            'password_protected',
            'burn_after_read',
            'read_count',
            'created_at',
            'expires_at',
        ])
        ->paginate(10)
        ->withQueryString()
        ->through(fn (Paste $paste) => [
            'key' => $paste->key,
            'language' => $paste->language,
            'encrypted' => $paste->encrypted,
            'password_protected' => $paste->password_protected,
            'burn_after_read' => $paste->burn_after_read,
            'read_count' => $paste->read_count,
            'created_at' => $paste->created_at?->toIso8601String(),
            'expires_at' => $paste->expires_at?->toIso8601String(),
        ]);

    return Inertia::render('Dashboard', [
        'pastes' => $pastes,
    ]);
})->middleware(['auth'])->name('dashboard');

Route::get('auth/discord', [DiscordController::class, 'redirect'])->name('auth.discord');
Route::get('auth/discord/callback', [DiscordController::class, 'callback'])->name('auth.discord.callback');
Route::post('logout', [DiscordController::class, 'logout'])->name('logout');
Route::delete('account', [DiscordController::class, 'destroy'])->middleware(['auth'])->name('account.destroy');

