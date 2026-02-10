<script lang="ts">
    import AppLayout from '../layouts/AppLayout.svelte';
</script>

<svelte:head>
    <title>Welcome</title>
</svelte:head>

<AppLayout>
    <section class="flex flex-col gap-6">
        <div class="space-y-3">
            <h1 class="text-3xl font-semibold tracking-tight">Welcome to Laracord</h1>
            <p class="max-w-2xl text-zinc-600 dark:text-zinc-400">Sign in with Discord to access your dashboard and start managing your community.</p>
        </div>
        <div class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
            <h2 class="text-xl font-medium">Get started</h2>
            <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">Connect your Discord account and jump right into your private dashboard.</p>
        </div>
    </section>
</AppLayout>
