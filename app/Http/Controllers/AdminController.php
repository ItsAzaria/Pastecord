<?php

namespace App\Http\Controllers;

use App\Models\Paste;
use App\Models\User;
use Carbon\CarbonImmutable;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class AdminController extends Controller
{
    public function index()
    {
        $today = CarbonImmutable::today();
        $trendDays = 14;
        $trendStart = $today->subDays($trendDays - 1)->startOfDay();
        $last24Hours = now()->subDay();

        $totalUsers = User::query()->count();
        $totalPastes = Paste::query()->count();
        $newUsersLast24h = User::query()->where('created_at', '>=', $last24Hours)->count();
        $newPastesLast24h = Paste::query()->where('created_at', '>=', $last24Hours)->count();
        $encryptedPastes = Paste::query()->where('encrypted', true)->count();
        $burnAfterReadPastes = Paste::query()->where('burn_after_read', true)->count();
        $activePastes = Paste::query()
            ->where(function ($query) {
                $query->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->count();
        $totalReads = (int) Paste::query()->sum('read_count');

        $usersByDate = User::query()
            ->selectRaw('DATE(created_at) as date, COUNT(*) as total')
            ->where('created_at', '>=', $trendStart)
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('total', 'date');

        $pastesByDate = Paste::query()
            ->selectRaw('DATE(created_at) as date, COUNT(*) as total')
            ->where('created_at', '>=', $trendStart)
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('total', 'date');

        $trend = [];
        for ($offset = 0; $offset < $trendDays; $offset++) {
            $date = $trendStart->addDays($offset);
            $key = $date->toDateString();

            $trend[] = [
                'date' => $key,
                'label' => $date->format('M j'),
                'users' => (int) ($usersByDate[$key] ?? 0),
                'pastes' => (int) ($pastesByDate[$key] ?? 0),
            ];
        }

        $topLanguages = Paste::query()
            ->select('language', DB::raw('COUNT(*) as total'))
            ->groupBy('language')
            ->orderByDesc('total')
            ->limit(5)
            ->get()
            ->map(fn ($row) => [
                'language' => $row->language ?: 'plaintext',
                'total' => (int) $row->total,
            ])
            ->values();

        return Inertia::render('AdminDashboard', [
            'analytics' => [
                'kpis' => [
                    'total_users' => $totalUsers,
                    'total_pastes' => $totalPastes,
                    'new_users_24h' => $newUsersLast24h,
                    'new_pastes_24h' => $newPastesLast24h,
                    'active_pastes' => $activePastes,
                    'encrypted_pastes' => $encryptedPastes,
                    'burn_after_read_pastes' => $burnAfterReadPastes,
                    'total_reads' => $totalReads,
                ],
                'trend' => $trend,
                'top_languages' => $topLanguages,
            ],
        ]);
    }

    public function destroyPaste(Request $request, string $key)
    {
        $paste = Paste::where('key', $key)->firstOrFail();

        $paste->delete();

        return response()->json([
            'deleted' => true,
            'key' => $key,
        ]);
    }

    public function triggerException()
    {
        Log::channel('discord')->error('Manual admin-triggered exception test.', [
            'source' => 'admin.dashboard.button',
        ]);

        throw new Exception('Manual admin-triggered exception for Discord logging test.');
    }
}
