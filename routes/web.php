<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\Auth\DiscordController;
use App\Http\Controllers\PasteController;

Route::get('/', function () {
    return Inertia::render('Index', [
    ]);
})->name('home');

Route::post('pastes', [PasteController::class, 'store'])->name('pastes.store');
Route::get('{key}', [PasteController::class, 'show'])
    ->where('key', '[A-Za-z0-9]{32}')
    ->name('pastes.show');

Route::get('dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth'])->name('dashboard');

Route::get('auth/discord', [DiscordController::class, 'redirect'])->name('auth.discord');
Route::get('auth/discord/callback', [DiscordController::class, 'callback'])->name('auth.discord.callback');
Route::post('logout', [DiscordController::class, 'logout'])->name('logout');

