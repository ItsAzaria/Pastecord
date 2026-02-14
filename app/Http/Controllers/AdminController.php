<?php

namespace App\Http\Controllers;

use App\Models\Paste;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class AdminController extends Controller
{
    public function index()
    {
        return Inertia::render('AdminDashboard');
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
