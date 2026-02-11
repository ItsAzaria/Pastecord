<script lang="ts">
    import { onMount } from 'svelte';
    import { page } from '@inertiajs/svelte';
    import hljs from 'highlight.js';
    import AppLayout from '../layouts/AppLayout.svelte';
    import { decrypt, decryptWithPassword } from '../lib/crypto';

    type PastePayload = {
        key: string;
        content: string;
        encrypted: boolean;
        password_protected: boolean;
        init_vector: string | null;
        salt: string | null;
        language: string | null;
        burn_after_read: boolean;
        expires_at: string | null;
    };

    $: paste = ($page.props.paste ?? null) as PastePayload | null;

    let decryptedContent = '';
    let decryptError = '';
    let isDecrypting = false;
    let passwordInput = '';
    let codeBlock: HTMLElement | null = null;

    const getKeyFromHash = () => {
        if (typeof window === 'undefined') return '';
        const hash = window.location.hash.replace('#', '');
        const params = new URLSearchParams(hash);
        return params.get('key') ?? '';
    };

    const tryAutoDecrypt = async () => {
        if (!paste) return;
        decryptError = '';

        if (!paste.encrypted) {
            decryptedContent = paste.content;
            return;
        }

        if (paste.password_protected) {
            return;
        }

        const key = getKeyFromHash();
        if (!key) {
            decryptError = 'Missing client key in the URL.';
            return;
        }

        if (!paste.init_vector) {
            decryptError = 'Missing initialization vector.';
            return;
        }

        isDecrypting = true;
        try {
            decryptedContent = await decrypt({
                ciphertext: paste.content,
                iv: paste.init_vector,
                key,
            });
        } catch (error) {
            decryptError = error instanceof Error ? error.message : 'Failed to decrypt paste.';
        } finally {
            isDecrypting = false;
        }
    };

    const handlePasswordDecrypt = async () => {
        if (!paste) return;
        decryptError = '';

        if (!passwordInput.trim()) {
            decryptError = 'Enter the password to decrypt this paste.';
            return;
        }

        if (!paste.init_vector || !paste.salt) {
            decryptError = 'Missing encryption metadata.';
            return;
        }

        isDecrypting = true;
        try {
            decryptedContent = await decryptWithPassword(
                {
                    ciphertext: paste.content,
                    iv: paste.init_vector,
                    salt: paste.salt,
                },
                passwordInput.trim(),
            );
            passwordInput = '';
        } catch (error) {
            decryptError = error instanceof Error ? error.message : 'Failed to decrypt paste.';
        } finally {
            isDecrypting = false;
        }
    };

    onMount(() => {
        tryAutoDecrypt();
    });

    $: if (paste) {
        tryAutoDecrypt();
    }

    const applyHighlight = () => {
        if (!codeBlock) return;
        const content = decryptedContent ?? '';
        codeBlock.textContent = content;
        codeBlock.className = 'hljs';

        if (!content) return;

        const language = paste?.language ?? 'auto';
        if (language && language !== 'auto' && language !== 'plaintext') {
            codeBlock.classList.add(`language-${language}`);
            try {
                hljs.highlightElement(codeBlock);
                return;
            } catch (error) {
                // fall through to auto detect
            }
        }

        const result = hljs.highlightAuto(content);
        codeBlock.innerHTML = result.value;
    };

    $: if (codeBlock) {
        decryptedContent;
        paste?.language;
        applyHighlight();
    }
</script>

<svelte:head>
    <title>Paste {paste?.key ?? ''}</title>
</svelte:head>

<AppLayout>
    <section class="flex flex-col gap-6">
        <div class="space-y-2">
            <h1 class="text-3xl font-semibold tracking-tight">Paste</h1>
            {#if paste}
                <p class="text-sm text-zinc-500">Key: <span class="font-mono">{paste.key}</span></p>
            {/if}
        </div>

        {#if paste?.encrypted && paste.password_protected}
            <div class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                <h2 class="text-lg font-medium">Password required</h2>
                <p class="mt-2 text-sm text-zinc-500">Enter the password to decrypt this paste.</p>
                <div class="mt-4 flex flex-col gap-3 sm:flex-row sm:items-center">
                    <input
                        type="password"
                        bind:value={passwordInput}
                        placeholder="Password"
                        class="w-full rounded-md border border-zinc-200 bg-white px-3 py-2 text-sm dark:border-zinc-800 dark:bg-zinc-950"
                    />
                    <button
                        type="button"
                        on:click={handlePasswordDecrypt}
                        class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-indigo-500 disabled:cursor-not-allowed disabled:opacity-70"
                        disabled={isDecrypting}
                    >
                        {isDecrypting ? 'Decrypting...' : 'Decrypt'}
                    </button>
                </div>
            </div>
        {/if}

        {#if decryptError}
            <p class="text-sm text-red-600">{decryptError}</p>
        {/if}

        <div class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
            <h2 class="text-lg font-medium">Content</h2>
            <pre
                class="mt-4 max-h-[32rem] overflow-auto rounded-md border border-zinc-200 bg-white p-4 text-sm text-zinc-900 dark:border-zinc-800 dark:bg-zinc-950 dark:text-zinc-100">
                <code bind:this={codeBlock} class="hljs"></code>
            </pre>
        </div>
    </section>
</AppLayout>
