<?php

namespace App\Http\Controllers;

use App\Models\Paste;
use Illuminate\Http\Request;
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
}
