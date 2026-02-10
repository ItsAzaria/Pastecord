<script lang="ts">
    import AppHeader from '../components/AppHeader.svelte';
</script>

<div class="min-h-screen bg-zinc-50 text-zinc-900 dark:bg-zinc-950 dark:text-zinc-100">
    <AppHeader />
    <main class="mx-auto w-full max-w-6xl px-4 py-10">
        <slot />
    </main>
</div>
