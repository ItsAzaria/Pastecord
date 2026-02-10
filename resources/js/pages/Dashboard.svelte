<script lang="ts">
    import AppLayout from '../layouts/AppLayout.svelte';
    import { page } from '@inertiajs/svelte';

    $: user = $page.props.auth?.user ?? null;
</script>

<svelte:head>
    <title>Dashboard</title>
</svelte:head>

<AppLayout>
    <section class="flex flex-col gap-6">
        <div class="space-y-2">
            <h1 class="text-3xl font-semibold tracking-tight">Dashboard</h1>
            <p class="text-zinc-600 dark:text-zinc-400">This area is only visible to authenticated users.</p>
        </div>

        <div class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
            <h2 class="text-lg font-medium">Connected account</h2>
            {#if user}
                <div class="mt-4 flex items-center gap-4">
                    <img src={user.avatar || 'https://cdn.discordapp.com/embed/avatars/0.png'} alt="Discord avatar" class="h-14 w-14 rounded-full" />
                    <div>
                        <div class="text-base font-semibold">{user.name}</div>
                        {#if user.discord_username}
                            <div class="text-sm text-zinc-500">@{user.discord_username}</div>
                        {/if}
                        {#if user.email}
                            <div class="text-sm text-zinc-500">{user.email}</div>
                        {/if}
                    </div>
                </div>
            {/if}
        </div>
    </section>
</AppLayout>
