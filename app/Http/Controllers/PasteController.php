<?php

namespace App\Http\Controllers;

use App\Models\Paste;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class PasteController extends Controller
{
    public function show(string $key)
    {
        $paste = Paste::where('key', $key)->firstOrFail();

        return Inertia::render('Paste', [
            'paste' => [
                'key' => $paste->key,
                'content' => $paste->content,
                'encrypted' => $paste->encrypted,
                'password_protected' => $paste->password_protected,
                'init_vector' => $paste->init_vector,
                'salt' => $paste->salt,
                'language' => $paste->language,
                'burn_after_read' => $paste->burn_after_read,
                'expires_at' => $paste->expires_at?->toIso8601String(),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'content' => ['required', 'string'],
            'encrypted' => ['sometimes', 'boolean'],
            'password_protected' => ['sometimes', 'boolean'],
            'init_vector' => ['nullable', 'string', 'max:128', Rule::requiredIf($request->boolean('encrypted'))],
            'salt' => ['nullable', 'string', 'max:128', Rule::requiredIf($request->boolean('password_protected'))],
            'language' => ['nullable', 'string', 'max:32'],
            'burn_after_read' => ['sometimes', 'boolean'],
            'expires_in_seconds' => ['nullable', 'integer', 'min:0', 'max:31536000'],
        ]);

        $expiresIn = (int) ($validated['expires_in_seconds'] ?? 0);
        $expiresAt = $expiresIn > 0 ? now()->addSeconds($expiresIn) : null;

        do {
            $key = Str::random(32);
        } while (Paste::where('key', $key)->exists());

        $paste = Paste::create([
            'key' => $key,
            'content' => $validated['content'],
            'encrypted' => (bool) ($validated['encrypted'] ?? false),
            'password_protected' => (bool) ($validated['password_protected'] ?? false),
            'init_vector' => $validated['init_vector'] ?? null,
            'salt' => $validated['salt'] ?? null,
            'language' => $validated['language'] ?? 'plaintext',
            'discord_id' => optional($request->user())->discord_id,
            'expires_at' => $expiresAt,
            'burn_after_read' => (bool) ($validated['burn_after_read'] ?? false),
            'read_count' => 0,
            'content_hash' => hash('sha256', $validated['content']),
        ]);

        return response()->json([
            'key' => $paste->key,
        ], 201);
    }

    public function destroy(Request $request, string $key)
    {
        $user = $request->user();

        if (!$user?->discord_id) {
            abort(403, 'Discord account not linked.');
        }

        $paste = Paste::where('key', $key)->firstOrFail();

        if ($paste->discord_id !== $user->discord_id) {
            abort(403, 'You are not allowed to delete this paste.');
        }

        $paste->delete();

        return response()->json([
            'deleted' => true,
        ]);
    }

    public function storeDocumentApi(Request $request)
    {
        $discordId = $request->header('X-Discord-Id');

        if ($discordId !== null) {
            $validator = Validator::make(
                ['discord_id' => $discordId],
                ['discord_id' => ['string', 'max:32', 'regex:/^\d+$/']]
            );

            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Validation failed.',
                    'errors' => $validator->errors(),
                ], 422);
            }
        }

        $content = $request->input('content');
        if (!is_string($content) || trim($content) === '') {
            $content = $request->getContent();
        }

        if (!is_string($content) || trim($content) === '') {
            return response()->json([
                'message' => 'Content is required.',
                'errors' => ['content' => ['Content is required.']],
            ], 422);
        }

        $language = 'auto';

        do {
            $key = Str::random(32);
        } while (Paste::where('key', $key)->exists());

        $paste = Paste::create([
            'key' => $key,
            'content' => $content,
            'encrypted' => false,
            'password_protected' => false,
            'init_vector' => null,
            'salt' => null,
            'language' => $language,
            'discord_id' => $discordId,
            'expires_at' => null,
            'burn_after_read' => false,
            'read_count' => 0,
            'content_hash' => hash('sha256', $content),
        ]);

        return response()->json([
            'key' => $paste->key,
        ], 200);
    }

    public function showDocumentApi(string $key)
    {
        $paste = Paste::where('key', $key)->first();

        if (!$paste) {
            return response()->json([
                'message' => 'Not found.',
            ], 404);
        }

        if ($paste->expires_at && $paste->expires_at->isPast()) {
            $paste->delete();
            return response()->json([
                'message' => 'Not found.',
            ], 404);
        }

        if ($paste->encrypted || $paste->password_protected) {
            return response()->json([
                'message' => 'Not found.',
            ], 404);
        }

        $paste->increment('read_count');

        $payload = [
            'data' => $paste->content,
            'key' => $paste->key,
        ];

        if ($paste->burn_after_read) {
            $paste->delete();
        }

        return response()->json($payload);
    }
}
