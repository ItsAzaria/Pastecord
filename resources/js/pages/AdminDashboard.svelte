<script lang="ts">
    import AppLayout from '../layouts/AppLayout.svelte';

    const getCsrfToken = () => (typeof document !== 'undefined' ? document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') : null);

    let pasteKey = '';
    let message = '';
    let error = '';
    let isDeleting = false;
    let isTriggeringException = false;

    const handleDelete = async () => {
        if (isDeleting) return;
        message = '';
        error = '';

        const trimmedKey = pasteKey.trim();
        if (!trimmedKey) {
            error = 'Enter a paste key to delete.';
            return;
        }

        isDeleting = true;
        const csrfToken = getCsrfToken();

        try {
            const response = await fetch(`/admin/pastes/${encodeURIComponent(trimmedKey)}`, {
                method: 'DELETE',
                headers: {
                    Accept: 'application/json',
                    ...(csrfToken ? { 'X-CSRF-TOKEN': csrfToken } : {}),
                },
            });

            if (!response.ok) {
                const errorPayload = await response.json().catch(() => null);
                error = errorPayload?.message ?? 'Failed to delete paste.';
                return;
            }

            message = `Paste ${trimmedKey} deleted.`;
            pasteKey = '';
        } catch (fetchError) {
            error = fetchError instanceof Error ? fetchError.message : 'Failed to delete paste.';
        } finally {
            isDeleting = false;
        }
    };

    const handleTriggerException = async () => {
        if (isTriggeringException) return;

        message = '';
        error = '';
        isTriggeringException = true;

        const csrfToken = getCsrfToken();

        try {
            const response = await fetch('/admin/trigger-exception', {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    ...(csrfToken ? { 'X-CSRF-TOKEN': csrfToken } : {}),
                },
            });

            if (response.status >= 500) {
                message = 'Test exception triggered. Check Discord for the error log.';
                return;
            }

            if (!response.ok) {
                const errorPayload = await response.json().catch(() => null);
                error = errorPayload?.message ?? 'Failed to trigger test exception.';
                return;
            }

            message = 'Request completed, but no exception was raised.';
        } catch (fetchError) {
            error = fetchError instanceof Error ? fetchError.message : 'Failed to trigger test exception.';
        } finally {
            isTriggeringException = false;
        }
    };
</script>

<svelte:head>
    <title>Admin Dashboard</title>
</svelte:head>

<AppLayout>
    <section class="flex flex-col gap-6">
        <div class="space-y-2">
            <h1 class="text-3xl font-semibold tracking-tight">Admin Dashboard</h1>
            <p class="text-sm text-zinc-500">Delete any paste by its key.</p>
        </div>

        <div class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
            <form class="flex flex-col gap-4" on:submit|preventDefault={handleDelete}>
                <label class="text-sm font-medium text-zinc-700 dark:text-zinc-200" for="paste-key">Paste key</label>
                <input
                    id="paste-key"
                    name="paste-key"
                    type="text"
                    placeholder="32-character paste key"
                    class="w-full rounded-md border border-zinc-200 bg-white px-3 py-2 text-sm text-zinc-900 shadow-sm focus:border-indigo-400 focus:outline-none focus:ring-2 focus:ring-indigo-100 dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100 dark:focus:border-indigo-500 dark:focus:ring-indigo-500/20"
                    bind:value={pasteKey}
                />
                <div class="flex flex-wrap items-center gap-3">
                    <button
                        type="submit"
                        class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-indigo-500 disabled:cursor-not-allowed disabled:opacity-70"
                        disabled={isDeleting}
                    >
                        {isDeleting ? 'Deleting...' : 'Delete paste'}
                    </button>
                    <span class="text-xs text-zinc-500">Deletion is immediate and cannot be undone.</span>
                </div>
            </form>

            <div class="mt-6 border-t border-zinc-200 pt-6 dark:border-zinc-800">
                <p class="text-sm text-zinc-500">Trigger a test exception to verify Discord error logging.</p>
                <button
                    type="button"
                    class="mt-3 rounded-md bg-red-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-red-500 disabled:cursor-not-allowed disabled:opacity-70"
                    disabled={isTriggeringException}
                    on:click={handleTriggerException}
                >
                    {isTriggeringException ? 'Triggering...' : 'Trigger test exception'}
                </button>
            </div>

            {#if message}
                <div
                    class="mt-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700 dark:border-emerald-500/30 dark:bg-emerald-500/10 dark:text-emerald-200"
                >
                    {message}
                </div>
            {/if}

            {#if error}
                <div
                    class="mt-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-500/30 dark:bg-red-500/10 dark:text-red-200"
                >
                    {error}
                </div>
            {/if}
        </div>
    </section>
</AppLayout>
