<script lang="ts">
    import AppLayout from '../layouts/AppLayout.svelte';
    import { page } from '@inertiajs/svelte';

    const status = ($page.props.status as number | undefined) ?? 404;

    const titleMap: Record<number, string> = {
        404: 'Page not found',
        403: 'Access denied',
        500: 'Something went wrong',
    };

    const descriptionMap: Record<number, string> = {
        404: "That link doesn't point to a paste or page we can find.",
        403: 'You do not have access to this page.',
        500: 'We hit a snag while loading this page.',
    };

    const title = titleMap[status] ?? 'Page not found';
    const description = descriptionMap[status] ?? "That link doesn't point to a paste or page we can find.";
</script>

<svelte:head>
    <title>{title}</title>
</svelte:head>

<AppLayout>
    <section class="flex min-h-[60vh] flex-col items-center justify-center gap-6 text-center">
        <div class="flex h-24 w-24 items-center justify-center rounded-full bg-indigo-100 text-indigo-700 dark:bg-indigo-500/20 dark:text-indigo-200">
            <span class="text-3xl font-semibold">{status}</span>
        </div>
        <div class="space-y-2">
            <h1 class="text-3xl font-semibold tracking-tight">{title}</h1>
            <p class="text-sm text-zinc-500">{description}</p>
        </div>
        <div class="flex flex-wrap items-center justify-center gap-3">
            <a href="/" class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-indigo-500">
                Create a new paste
            </a>
            <button
                type="button"
                class="rounded-md border border-zinc-200 px-4 py-2 text-sm font-medium text-zinc-700 transition hover:border-zinc-300 hover:text-zinc-900 dark:border-zinc-800 dark:text-zinc-300 dark:hover:text-white"
                on:click={() => history.back()}
            >
                Go back
            </button>
        </div>
    </section>
</AppLayout>
