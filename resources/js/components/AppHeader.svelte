<script lang="ts">
    import { page } from '@inertiajs/svelte';

    const csrfToken = typeof document !== 'undefined' ? (document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '') : '';

    $: user = $page.props.auth?.user ?? null;
    $: avatarUrl = user?.avatar || 'https://cdn.discordapp.com/embed/avatars/0.png';
</script>

<header class="border-b border-zinc-200 bg-white/80 backdrop-blur dark:border-zinc-800 dark:bg-zinc-950/80">
    <div class="mx-auto flex w-full max-w-6xl items-center justify-between px-4 py-4">
        <a href="/" class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">Laracord</a>
        <div class="flex items-center gap-3">
            {#if user}
                <div class="flex items-center gap-3 rounded-full border border-zinc-200 px-3 py-1 dark:border-zinc-800">
                    <img src={avatarUrl} alt="Discord avatar" class="h-8 w-8 rounded-full" />
                    <div class="text-sm">
                        <div class="font-medium text-zinc-900 dark:text-zinc-100">{user.name}</div>
                        {#if user.discord_username}
                            <div class="text-xs text-zinc-500">@{user.discord_username}</div>
                        {/if}
                    </div>
                </div>
                <form method="post" action="/logout">
                    <input type="hidden" name="_token" value={csrfToken} />
                    <button
                        type="submit"
                        class="rounded-md border border-zinc-200 px-3 py-2 text-sm text-zinc-700 transition hover:border-zinc-300 hover:text-zinc-900 dark:border-zinc-800 dark:text-zinc-300 dark:hover:text-white"
                    >
                        Log out
                    </button>
                </form>
            {:else}
                <a href="/auth/discord" class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-indigo-500">
                    Log in with Discord
                </a>
            {/if}
        </div>
    </div>
</header>
