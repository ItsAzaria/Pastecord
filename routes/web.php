<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\Auth\DiscordController;

Route::get('/', function () {
    return Inertia::render('Welcome', [
    ]);
})->name('home');

Route::get('dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth'])->name('dashboard');

Route::get('auth/discord', [DiscordController::class, 'redirect'])->name('auth.discord');
Route::get('auth/discord/callback', [DiscordController::class, 'callback'])->name('auth.discord.callback');
Route::post('logout', [DiscordController::class, 'logout'])->name('logout');

