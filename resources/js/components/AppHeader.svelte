<script lang="ts">
    import { onMount } from 'svelte';
    import { page } from '@inertiajs/svelte';

    const csrfToken = typeof document !== 'undefined' ? (document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '') : '';

    $: user = $page.props.auth?.user ?? null;
    $: avatarUrl = user?.avatar || 'https://cdn.discordapp.com/embed/avatars/0.png';

    let theme: 'light' | 'dark' = 'light';

    const applyTheme = (value: 'light' | 'dark') => {
        if (typeof document === 'undefined') return;
        document.documentElement.classList.toggle('dark', value === 'dark');
    };

    const toggleTheme = () => {
        theme = theme === 'dark' ? 'light' : 'dark';
        applyTheme(theme);
        if (typeof localStorage !== 'undefined') {
            localStorage.setItem('theme', theme);
        }
    };

    onMount(() => {
        const stored = typeof localStorage !== 'undefined' ? localStorage.getItem('theme') : null;
        if (stored === 'light' || stored === 'dark') {
            theme = stored;
        } else if (typeof window !== 'undefined') {
            theme = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
        }
        applyTheme(theme);
    });
</script>

<svelte:head>
    <title>Pastecord</title>
</svelte:head>

<header class="h-16 border-b border-zinc-200 bg-white/80 backdrop-blur dark:border-zinc-800 dark:bg-zinc-950/80">
    <div class="flex h-full w-full items-center justify-between px-6">
        <a href="/" class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">Laracord</a>
        <div class="flex items-center gap-3">
            <button
                type="button"
                on:click={toggleTheme}
                class="relative inline-flex h-8 w-14 items-center rounded-full border border-zinc-200 bg-zinc-100 transition hover:border-zinc-300 dark:border-zinc-800 dark:bg-zinc-900"
                aria-label={theme === 'dark' ? 'Switch to light mode' : 'Switch to dark mode'}
                role="switch"
                aria-checked={theme === 'dark'}
            >
                <span
                    class={`inline-block h-6 w-6 transform rounded-full bg-white shadow transition ${
                        theme === 'dark' ? 'translate-x-7 bg-zinc-200' : 'translate-x-1'
                    }`}
                ></span>
            </button>
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
