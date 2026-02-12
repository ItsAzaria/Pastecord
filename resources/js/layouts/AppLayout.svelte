<script lang="ts">
    import AppHeader from '../components/AppHeader.svelte';
    import ThemeToggle from '../components/ThemeToggle.svelte';

    export let mainClass = 'mx-auto w-full max-w-6xl px-4 py-10';
</script>

<div class="min-h-screen bg-zinc-50 text-zinc-900 dark:bg-zinc-950 dark:text-zinc-100 flex flex-col">
    <AppHeader />
    <main class={`flex-1 min-h-0 ${mainClass}`}>
        <slot />
    </main>
    <ThemeToggle />
</div>
