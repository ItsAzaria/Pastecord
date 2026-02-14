<?php

namespace App\Jobs;

use App\Models\Paste;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class DeleteExpiredPastes implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        $now = now();

        Paste::query()
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', $now)
            ->delete();

        Paste::query()
            ->where('burn_after_read', true)
            ->where('read_count', '>=', 2)
            ->delete();
    }
}
