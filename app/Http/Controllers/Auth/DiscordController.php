<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

class DiscordController extends Controller
{
    public function redirect(): RedirectResponse
    {
        return Socialite::driver('discord')
            ->scopes(['identify', 'email'])
            ->redirect();
    }

    public function callback(Request $request): RedirectResponse
    {
        $discordUser = Socialite::driver('discord')->user();

        $user = User::updateOrCreate(
            ['discord_id' => $discordUser->getId()],
            [
                'name' => $discordUser->getName() ?: ($discordUser->getNickname() ?: 'Discord User'),
                'email' => $discordUser->getEmail(),
                'discord_username' => $discordUser->getNickname() ?: $discordUser->getName(),
                'discord_avatar' => $discordUser->getAvatar(),
            ]
        );

        Auth::login($user, true);

        return redirect()->intended(route('dashboard'));
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }
}
