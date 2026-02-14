<script lang="ts">
    import AppLayout from '../layouts/AppLayout.svelte';
    import { page } from '@inertiajs/svelte';

    type PasteSummary = {
        key: string;
        language: string | null;
        encrypted: boolean;
        password_protected: boolean;
        burn_after_read: boolean;
        read_count: number;
        created_at: string | null;
        expires_at: string | null;
    };

    type PaginationLink = {
        url: string | null;
        label: string;
        active: boolean;
    };

    type PaginatedPastes = {
        data: PasteSummary[];
        links: PaginationLink[];
        total: number;
    };

    const formatDate = (value: string | null) => {
        if (!value) return 'Never';
        const date = new Date(value);
        if (Number.isNaN(date.getTime())) return 'Unknown';
        return new Intl.DateTimeFormat('en-US', {
            dateStyle: 'medium',
            timeStyle: 'short',
        }).format(date);
    };

    const isClientEncrypted = (paste: PasteSummary) => paste.encrypted && !paste.password_protected;

    const getCsrfToken = () => (typeof document !== 'undefined' ? document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') : null);

    let localPastes: PasteSummary[] = [];
    let deleteMessage = '';
    let deleteError = '';
    let blockedMessage = '';
    let accountMessage = '';
    let accountError = '';
    let isDeletingAccount = false;
    let isAccountConfirmOpen = false;
    let isPasteConfirmOpen = false;
    let pasteToDelete: PasteSummary | null = null;
    let isDeletingPaste = false;

    $: user = $page.props.auth?.user ?? null;
    $: pastes = ($page.props.pastes as PaginatedPastes | undefined)?.data ?? [];
    $: pagination = ($page.props.pastes as PaginatedPastes | undefined)?.links ?? [];
    $: totalPastes = ($page.props.pastes as PaginatedPastes | undefined)?.total ?? 0;
    $: localPastes = pastes;

    const handlePasteClick = (event: MouseEvent, paste: PasteSummary) => {
        if (!isClientEncrypted(paste)) return;
        event.preventDefault();
        blockedMessage = 'This paste is encrypted without a password, so it can only be opened with a known decryption key.';
    };

    const handleDelete = async (paste: PasteSummary) => {
        if (isDeletingPaste) return;
        deleteMessage = '';
        deleteError = '';
        isDeletingPaste = true;
        const csrfToken = getCsrfToken();

        try {
            const response = await fetch(`/pastes/${paste.key}`, {
                method: 'DELETE',
                headers: {
                    Accept: 'application/json',
                    ...(csrfToken ? { 'X-CSRF-TOKEN': csrfToken } : {}),
                },
            });

            if (!response.ok) {
                const errorPayload = await response.json().catch(() => null);
                deleteError = errorPayload?.message ?? 'Failed to delete paste.';
                return;
            }

            localPastes = localPastes.filter((item) => item.key !== paste.key);
            deleteMessage = 'Paste deleted.';
        } catch (error) {
            deleteError = error instanceof Error ? error.message : 'Failed to delete paste.';
        } finally {
            isDeletingPaste = false;
            pasteToDelete = null;
        }
    };

    const handleDeleteAccount = async () => {
        if (isDeletingAccount) return;
        accountMessage = '';
        accountError = '';
        isDeletingAccount = true;
        const csrfToken = getCsrfToken();

        try {
            const response = await fetch('/account', {
                method: 'DELETE',
                headers: {
                    Accept: 'application/json',
                    ...(csrfToken ? { 'X-CSRF-TOKEN': csrfToken } : {}),
                },
            });

            if (!response.ok) {
                const errorPayload = await response.json().catch(() => null);
                accountError = errorPayload?.message ?? 'Failed to delete account.';
                return;
            }

            accountMessage = 'Account deleted.';
            if (typeof window !== 'undefined') {
                window.location.href = '/';
            }
        } catch (error) {
            accountError = error instanceof Error ? error.message : 'Failed to delete account.';
        } finally {
            isDeletingAccount = false;
        }
    };

    const openAccountConfirm = () => {
        accountMessage = '';
        accountError = '';
        isAccountConfirmOpen = true;
    };

    const closeAccountConfirm = () => {
        if (isDeletingAccount) return;
        isAccountConfirmOpen = false;
    };

    const confirmAccountDelete = async () => {
        if (isDeletingAccount) return;
        isAccountConfirmOpen = false;
        await handleDeleteAccount();
    };

    const openPasteConfirm = (paste: PasteSummary) => {
        deleteMessage = '';
        deleteError = '';
        pasteToDelete = paste;
        isPasteConfirmOpen = true;
    };

    const closePasteConfirm = () => {
        if (isDeletingPaste) return;
        isPasteConfirmOpen = false;
    };

    const confirmPasteDelete = async () => {
        if (!pasteToDelete || isDeletingPaste) return;
        isPasteConfirmOpen = false;
        await handleDelete(pasteToDelete);
    };
</script>

<svelte:head>
    <title>Dashboard</title>
</svelte:head>

<AppLayout>
    <section class="flex flex-col gap-6">
        <div class="space-y-2">
            <h1 class="text-3xl font-semibold tracking-tight">Dashboard</h1>
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
                        {#if user.discord_id}
                            <div class="text-sm text-zinc-500">Discord ID: {user.discord_id}</div>
                        {/if}
                    </div>
                </div>
                <div class="mt-6 flex flex-wrap items-center justify-between gap-3">
                    <p class="text-sm text-zinc-500">Delete your account and all pastes linked to your Discord ID.</p>
                    <button
                        type="button"
                        on:click={openAccountConfirm}
                        class="rounded-md border border-red-200 px-3 py-2 text-sm font-medium text-red-600 transition hover:border-red-300 hover:text-red-700 disabled:cursor-not-allowed disabled:opacity-70 dark:border-red-500/40 dark:text-red-300 dark:hover:border-red-400 dark:hover:text-red-200"
                        disabled={isDeletingAccount}
                    >
                        {isDeletingAccount ? 'Deleting...' : 'Delete account'}
                    </button>
                </div>
                {#if accountMessage}
                    <div
                        class="mt-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700 dark:border-emerald-500/30 dark:bg-emerald-500/10 dark:text-emerald-200"
                    >
                        {accountMessage}
                    </div>
                {/if}
                {#if accountError}
                    <div
                        class="mt-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-500/30 dark:bg-red-500/10 dark:text-red-200"
                    >
                        {accountError}
                    </div>
                {/if}
            {/if}
        </div>

        <div class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h2 class="text-lg font-medium">Your pastes</h2>
                    <p class="text-sm text-zinc-500">Showing {totalPastes} paste{totalPastes === 1 ? '' : 's'} linked to your Discord user ID.</p>
                </div>
                <a
                    href="/"
                    class="rounded-md border border-zinc-200 px-3 py-2 text-sm font-medium text-zinc-700 transition hover:border-zinc-300 hover:text-zinc-900 dark:border-zinc-800 dark:text-zinc-300 dark:hover:text-white"
                >
                    Create new paste
                </a>
            </div>

            {#if blockedMessage}
                <div
                    class="mt-4 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-700 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-200"
                >
                    {blockedMessage}
                </div>
            {/if}

            {#if deleteMessage}
                <div
                    class="mt-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700 dark:border-emerald-500/30 dark:bg-emerald-500/10 dark:text-emerald-200"
                >
                    {deleteMessage}
                </div>
            {/if}

            {#if deleteError}
                <div
                    class="mt-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-500/30 dark:bg-red-500/10 dark:text-red-200"
                >
                    {deleteError}
                </div>
            {/if}

            {#if localPastes.length === 0}
                <div class="mt-6 rounded-lg border border-dashed border-zinc-200 px-4 py-6 text-center text-sm text-zinc-500 dark:border-zinc-800">
                    No pastes yet. Create your first paste to see it listed here.
                </div>
            {:else}
                <div class="mt-6 grid gap-4">
                    {#each localPastes as paste}
                        <a
                            href={`/${paste.key}`}
                            on:click={(event) => handlePasteClick(event, paste)}
                            class="group block rounded-lg border border-zinc-200 p-4 transition hover:border-indigo-400 hover:bg-indigo-50/50 dark:border-zinc-800 dark:hover:border-indigo-500 dark:hover:bg-indigo-500/10"
                        >
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <div
                                    class="text-sm font-medium text-zinc-900 transition group-hover:text-indigo-700 dark:text-zinc-100 dark:group-hover:text-indigo-200"
                                >
                                    Paste {paste.key}
                                </div>
                                <div class="flex items-center gap-3 text-xs text-zinc-500">
                                    <span>Created {formatDate(paste.created_at)}</span>
                                    <button
                                        type="button"
                                        on:click|stopPropagation={(event) => {
                                            event.preventDefault();
                                            openPasteConfirm(paste);
                                        }}
                                        class="inline-flex items-center gap-1.5 rounded-md border border-zinc-200 px-2 py-1 text-xs font-medium text-zinc-700 transition hover:border-red-300 hover:text-red-600 dark:border-zinc-800 dark:text-zinc-300 dark:hover:border-red-400 dark:hover:text-red-200"
                                        aria-label="Delete paste"
                                    >
                                        <svg
                                            viewBox="0 0 24 24"
                                            class="h-3.5 w-3.5"
                                            fill="none"
                                            stroke="currentColor"
                                            stroke-width="1.8"
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                        >
                                            <path d="M3 6h18" />
                                            <path d="M8 6V4h8v2" />
                                            <path d="M19 6l-1 14H6L5 6" />
                                            <path d="M10 11v6" />
                                            <path d="M14 11v6" />
                                        </svg>
                                        <span class="sr-only">Delete</span>
                                    </button>
                                </div>
                            </div>
                            <div class="mt-2 flex flex-wrap gap-2 text-xs text-zinc-500">
                                <span class="rounded-full bg-zinc-100 px-2 py-1 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300">
                                    {paste.language || 'plaintext'}
                                </span>
                                {#if paste.encrypted}
                                    <span class="rounded-full bg-emerald-100 px-2 py-1 text-emerald-700 dark:bg-emerald-500/20 dark:text-emerald-200"
                                        >Encrypted</span
                                    >
                                {/if}
                                {#if paste.password_protected}
                                    <span class="rounded-full bg-amber-100 px-2 py-1 text-amber-700 dark:bg-amber-500/20 dark:text-amber-200"
                                        >Password</span
                                    >
                                {/if}
                                {#if paste.burn_after_read}
                                    <span class="rounded-full bg-red-100 px-2 py-1 text-red-700 dark:bg-red-500/20 dark:text-red-200"
                                        >Burn after read</span
                                    >
                                {/if}
                                <span class="rounded-full bg-zinc-100 px-2 py-1 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300">
                                    Reads: {paste.read_count}
                                </span>
                                <span class="rounded-full bg-zinc-100 px-2 py-1 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300">
                                    Expires: {formatDate(paste.expires_at)}
                                </span>
                            </div>
                        </a>
                    {/each}
                </div>

                {#if pagination.length > 1}
                    <nav class="mt-6 flex flex-wrap gap-2">
                        {#each pagination as link}
                            {#if link.url}
                                <a
                                    href={link.url}
                                    class={`rounded-md px-3 py-2 text-sm font-medium transition ${
                                        link.active
                                            ? 'bg-indigo-600 text-white'
                                            : 'border border-zinc-200 text-zinc-700 hover:border-zinc-300 hover:text-zinc-900 dark:border-zinc-800 dark:text-zinc-300 dark:hover:text-white'
                                    }`}
                                >
                                    {@html link.label}
                                </a>
                            {:else}
                                <span class="rounded-md px-3 py-2 text-sm text-zinc-400">{@html link.label}</span>
                            {/if}
                        {/each}
                    </nav>
                {/if}
            {/if}
        </div>
    </section>
</AppLayout>

{#if isAccountConfirmOpen}
    <div class="fixed inset-0 z-50 flex items-center justify-center px-4">
        <div class="absolute inset-0 bg-black/50" on:click={closeAccountConfirm}></div>
        <div class="relative w-full max-w-md rounded-xl border border-zinc-200 bg-white p-6 shadow-xl dark:border-zinc-800 dark:bg-zinc-950">
            <h3 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">Delete account?</h3>
            <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">
                This will permanently delete your account and all pastes linked to your Discord ID. This action cannot be undone.
            </p>
            <div class="mt-6 flex flex-wrap justify-end gap-3">
                <button
                    type="button"
                    on:click={closeAccountConfirm}
                    class="rounded-md border border-zinc-200 px-4 py-2 text-sm font-medium text-zinc-700 transition hover:border-zinc-300 hover:text-zinc-900 dark:border-zinc-800 dark:text-zinc-300 dark:hover:text-white"
                >
                    Cancel
                </button>
                <button
                    type="button"
                    on:click={confirmAccountDelete}
                    class="rounded-md bg-red-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-red-500 disabled:cursor-not-allowed disabled:opacity-70"
                    disabled={isDeletingAccount}
                >
                    {isDeletingAccount ? 'Deleting...' : 'Yes, delete'}
                </button>
            </div>
        </div>
    </div>
{/if}

{#if isPasteConfirmOpen}
    <div class="fixed inset-0 z-50 flex items-center justify-center px-4">
        <div class="absolute inset-0 bg-black/50" on:click={closePasteConfirm}></div>
        <div class="relative w-full max-w-md rounded-xl border border-zinc-200 bg-white p-6 shadow-xl dark:border-zinc-800 dark:bg-zinc-950">
            <h3 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">Delete paste?</h3>
            <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">
                This will permanently delete paste {pasteToDelete?.key}. This action cannot be undone.
            </p>
            <div class="mt-6 flex flex-wrap justify-end gap-3">
                <button
                    type="button"
                    on:click={closePasteConfirm}
                    class="rounded-md border border-zinc-200 px-4 py-2 text-sm font-medium text-zinc-700 transition hover:border-zinc-300 hover:text-zinc-900 dark:border-zinc-800 dark:text-zinc-300 dark:hover:text-white"
                >
                    Cancel
                </button>
                <button
                    type="button"
                    on:click={confirmPasteDelete}
                    class="rounded-md bg-red-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-red-500 disabled:cursor-not-allowed disabled:opacity-70"
                    disabled={isDeletingPaste}
                >
                    {isDeletingPaste ? 'Deleting...' : 'Yes, delete'}
                </button>
            </div>
        </div>
    </div>
{/if}
