<script lang="ts">
    import { onMount } from 'svelte';

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

<button
    type="button"
    on:click={toggleTheme}
    class="fixed bottom-4 right-4 z-50 inline-flex items-center gap-2 rounded-full border border-zinc-200 bg-white/90 px-4 py-2 text-sm font-medium text-zinc-800 shadow-lg backdrop-blur transition hover:border-zinc-300 hover:text-zinc-900 dark:border-zinc-800 dark:bg-zinc-950/90 dark:text-zinc-200 dark:hover:text-white"
    aria-label={theme === 'dark' ? 'Switch to light theme' : 'Switch to dark theme'}
>
    {#if theme === 'dark'}
        <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
            <path d="M17.293 13.293a8 8 0 0 1-10.586-10.586 8 8 0 1 0 10.586 10.586Z" />
        </svg>
        <span>Light theme</span>
    {:else}
        <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
            <path d="M10 15a5 5 0 1 0 0-10 5 5 0 0 0 0 10Z" />
            <path
                fill-rule="evenodd"
                d="M10 1a.75.75 0 0 1 .75.75v1.5a.75.75 0 0 1-1.5 0v-1.5A.75.75 0 0 1 10 1Zm0 13.75a.75.75 0 0 1 .75.75v1.5a.75.75 0 0 1-1.5 0v-1.5a.75.75 0 0 1 .75-.75ZM3.636 3.636a.75.75 0 0 1 1.06 0l1.061 1.06a.75.75 0 1 1-1.06 1.061L3.636 4.696a.75.75 0 0 1 0-1.06Zm9.546 9.546a.75.75 0 0 1 1.06 0l1.061 1.061a.75.75 0 0 1-1.06 1.06l-1.061-1.06a.75.75 0 0 1 0-1.061ZM1 10a.75.75 0 0 1 .75-.75h1.5a.75.75 0 0 1 0 1.5h-1.5A.75.75 0 0 1 1 10Zm13.75 0a.75.75 0 0 1 .75-.75h1.5a.75.75 0 0 1 0 1.5h-1.5a.75.75 0 0 1-.75-.75ZM3.636 16.364a.75.75 0 0 1 0-1.06l1.061-1.061a.75.75 0 1 1 1.06 1.06l-1.06 1.061a.75.75 0 0 1-1.061 0Zm9.546-9.546a.75.75 0 0 1 0-1.06l1.061-1.061a.75.75 0 1 1 1.06 1.06l-1.06 1.061a.75.75 0 0 1-1.061 0Z"
                clip-rule="evenodd"
            />
        </svg>
        <span>Dark theme</span>
    {/if}
</button>
