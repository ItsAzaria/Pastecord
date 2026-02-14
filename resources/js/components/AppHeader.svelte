<script lang="ts">
    import { page } from '@inertiajs/svelte';

    const DEFAULT_AVATAR = 'https://cdn.discordapp.com/embed/avatars/0.png';

    const csrfToken = typeof document !== 'undefined' ? (document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '') : '';

    $: user = $page.props.auth?.user ?? null;
    $: avatarUrl = user?.avatar || DEFAULT_AVATAR;

    const handleAvatarError = (event: Event) => {
        const image = event.currentTarget as HTMLImageElement;

        if (image.src !== DEFAULT_AVATAR) {
            image.src = DEFAULT_AVATAR;
        }
    };
</script>

<svelte:head>
    <title>Pastecord</title>
</svelte:head>

<header class="h-16 border-b border-zinc-200 bg-white/80 backdrop-blur dark:border-zinc-800 dark:bg-zinc-950/80">
    <div class="flex h-full w-full items-center justify-between px-6">
        <a href="/" class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">Pastecord</a>
        <div class="flex items-center gap-3">
            <a
                href="/"
                class="rounded-md border border-zinc-200 px-3 py-2 text-sm font-medium text-zinc-700 transition hover:border-zinc-300 hover:text-zinc-900 dark:border-zinc-800 dark:text-zinc-300 dark:hover:text-white"
            >
                New Paste
            </a>
            {#if user}
                <details class="relative">
                    <summary
                        class="flex list-none items-center gap-3 rounded-full border border-zinc-200 px-3 py-1 text-left transition hover:border-zinc-300 dark:border-zinc-800"
                        aria-label="Account menu"
                    >
                        <img src={avatarUrl} alt="Discord avatar" class="h-8 w-8 rounded-full" on:error={handleAvatarError} />
                        <div class="text-sm">
                            <div class="font-medium text-zinc-900 dark:text-zinc-100">{user.name}</div>
                            {#if user.discord_username}
                                <div class="text-xs text-zinc-500">@{user.discord_username}</div>
                            {/if}
                        </div>
                        <svg class="h-4 w-4 text-zinc-500 dark:text-zinc-400" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path
                                fill-rule="evenodd"
                                d="M5.23 7.21a.75.75 0 0 1 1.06.02L10 10.94l3.71-3.71a.75.75 0 1 1 1.06 1.06l-4.24 4.24a.75.75 0 0 1-1.06 0L5.21 8.27a.75.75 0 0 1 .02-1.06Z"
                                clip-rule="evenodd"
                            />
                        </svg>
                    </summary>
                    <div
                        class="absolute right-0 mt-2 w-44 overflow-hidden rounded-lg border border-zinc-200 bg-white shadow-lg dark:border-zinc-800 dark:bg-zinc-950"
                    >
                        <a
                            href="/dashboard"
                            class="flex w-full items-center gap-2 px-4 py-2 text-sm text-zinc-700 transition hover:bg-zinc-100 hover:text-zinc-900 dark:text-zinc-300 dark:hover:bg-zinc-900 dark:hover:text-white"
                        >
                            Dashboard
                        </a>
                        {#if user?.is_admin}
                            <a
                                href="/admin"
                                class="flex w-full items-center gap-2 px-4 py-2 text-sm text-zinc-700 transition hover:bg-zinc-100 hover:text-zinc-900 dark:text-zinc-300 dark:hover:bg-zinc-900 dark:hover:text-white"
                            >
                                Admin dashboard
                            </a>
                        {/if}
                        <form method="post" action="/logout">
                            <input type="hidden" name="_token" value={csrfToken} />
                            <button
                                type="submit"
                                class="flex w-full items-center gap-2 px-4 py-2 text-sm text-zinc-700 transition hover:bg-zinc-100 hover:text-zinc-900 dark:text-zinc-300 dark:hover:bg-zinc-900 dark:hover:text-white"
                            >
                                Log out
                            </button>
                        </form>
                    </div>
                </details>
            {:else}
                <a href="/auth/discord" class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-indigo-500">
                    Log in with Discord
                </a>
            {/if}
        </div>
    </div>
</header>

<style>
    summary::-webkit-details-marker {
        display: none;
    }

    summary {
        list-style: none;
    }
</style>
