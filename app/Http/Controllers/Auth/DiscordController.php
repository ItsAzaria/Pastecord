<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Paste;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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

    public function destroy(Request $request)
    {
        $user = $request->user();

        if (!$user?->discord_id) {
            abort(403, 'Discord account not linked.');
        }

        DB::transaction(function () use ($user) {
            Paste::where('discord_id', $user->discord_id)->delete();
            $user->delete();
        });

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json([
            'deleted' => true,
        ]);
    }
}
