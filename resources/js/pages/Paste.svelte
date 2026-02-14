<script lang="ts">
    import { onMount } from 'svelte';
    import { page } from '@inertiajs/svelte';
    import { HighlightAuto, LineNumbers } from 'svelte-highlight';
    import AppLayout from '../layouts/AppLayout.svelte';
    import { decrypt, decryptWithPassword } from '../lib/crypto';
    import { highlightLanguages } from '../lib/highlightLanguages';
    import horizonDark from 'svelte-highlight/styles/horizon-dark';
    import horizonLight from 'svelte-highlight/styles/horizon-light';

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
    let copyStatus = '';
    let languageOverride = 'auto';
    let languageOverrideTouched = false;
    const resolveLanguageNames = (language: string | null | undefined) => {
        if (!language || language === 'auto' || language === 'plaintext') return undefined;
        return [language];
    };

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

    const handleCopy = async () => {
        if (!decryptedContent) return;
        copyStatus = '';

        try {
            if (navigator?.clipboard?.writeText) {
                await navigator.clipboard.writeText(decryptedContent);
            } else {
                const textarea = document.createElement('textarea');
                textarea.value = decryptedContent;
                textarea.style.position = 'fixed';
                textarea.style.opacity = '0';
                document.body.appendChild(textarea);
                textarea.select();
                document.execCommand('copy');
                document.body.removeChild(textarea);
            }
            copyStatus = 'Copied!';
        } catch (error) {
            copyStatus = error instanceof Error ? error.message : 'Unable to copy.';
        } finally {
            if (copyStatus === 'Copied!') {
                setTimeout(() => {
                    copyStatus = '';
                }, 1500);
            }
        }
    };

    const handleViewRaw = () => {
        if (!decryptedContent || typeof window === 'undefined') return;
        const blob = new Blob([decryptedContent], { type: 'text/plain;charset=utf-8' });
        const url = URL.createObjectURL(blob);
        window.open(url, '_blank', 'noopener');
        setTimeout(() => URL.revokeObjectURL(url), 1000);
    };

    onMount(() => {
        tryAutoDecrypt();
    });

    $: if (paste) {
        tryAutoDecrypt();
    }

    $: if (paste && !languageOverrideTouched) {
        languageOverride = paste.language ?? 'auto';
    }

    $: languageNames = resolveLanguageNames(languageOverride);
</script>

<AppLayout mainClass="w-full max-w-none p-0 flex flex-col min-h-0">
    {#if decryptError}
        <section class="flex min-h-[70vh] items-center justify-center px-4 py-12">
            <div class="max-w-md text-center">
                <h1 class="text-xl font-semibold text-zinc-900 dark:text-zinc-100">Unable to decrypt paste</h1>
                <p class="mt-2 text-sm text-red-600">{decryptError}</p>
            </div>
        </section>
    {:else if !paste}
        <section class="flex min-h-[70vh] items-center justify-center px-4 py-12">
            <div class="max-w-md text-center">
                <h1 class="text-xl font-semibold text-zinc-900 dark:text-zinc-100">Paste not found</h1>
                <p class="mt-2 text-sm text-zinc-500">The paste you are looking for does not exist or has expired.</p>
            </div>
        </section>
    {:else if paste.encrypted && paste.password_protected && !decryptedContent}
        <section class="flex min-h-[70vh] items-center justify-center px-4 py-12">
            <div class="w-full max-w-md rounded-xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                <h1 class="text-lg font-medium text-zinc-900 dark:text-zinc-100">Password required</h1>
                <p class="mt-2 text-sm text-zinc-500">Enter the password to decrypt this paste.</p>
                <div class="mt-4 flex flex-col gap-3">
                    <input
                        type="password"
                        bind:value={passwordInput}
                        placeholder="Password"
                        class="w-full rounded-md border border-zinc-200 bg-white px-3 py-2 text-sm text-zinc-900 dark:border-zinc-800 dark:bg-zinc-950 dark:text-zinc-100"
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
        </section>
    {:else}
        <section class="flex flex-1 min-h-0 w-full flex-col">
            <div
                class="flex items-center justify-between border-b border-zinc-200 bg-white/90 px-4 py-2 text-sm text-zinc-600 dark:border-zinc-800 dark:bg-zinc-950/80 dark:text-zinc-300"
            >
                <div class="flex items-center gap-3">
                    <label class="flex items-center gap-2 text-xs text-zinc-500">
                        <span class="sr-only">Highlight language</span>
                        <select
                            bind:value={languageOverride}
                            on:change={() => {
                                languageOverrideTouched = true;
                            }}
                            class="rounded-md border border-zinc-200 bg-white px-2 py-1 text-xs text-zinc-700 dark:border-zinc-800 dark:bg-zinc-950 dark:text-zinc-300"
                        >
                            <option value="auto">Auto detect</option>
                            {#each highlightLanguages as option}
                                <option value={option.value}>{option.name}</option>
                            {/each}
                        </select>
                    </label>
                    {#if copyStatus}
                        <span class="text-xs text-emerald-600 dark:text-emerald-400">{copyStatus}</span>
                    {/if}
                </div>
                <div class="flex items-center gap-2">
                    <button
                        type="button"
                        on:click={handleCopy}
                        class="rounded-md border border-zinc-200 px-3 py-1.5 text-xs font-medium text-zinc-700 transition hover:border-zinc-300 hover:text-zinc-900 disabled:cursor-not-allowed disabled:opacity-60 dark:border-zinc-800 dark:text-zinc-300 dark:hover:text-white"
                        disabled={!decryptedContent}
                    >
                        Copy
                    </button>
                    <button
                        type="button"
                        on:click={handleViewRaw}
                        class="rounded-md border border-zinc-200 px-3 py-1.5 text-xs font-medium text-zinc-700 transition hover:border-zinc-300 hover:text-zinc-900 disabled:cursor-not-allowed disabled:opacity-60 dark:border-zinc-800 dark:text-zinc-300 dark:hover:text-white"
                        disabled={!decryptedContent}
                    >
                        View raw
                    </button>
                </div>
            </div>
            <div class="paste-highlight flex-1 min-h-0 w-full overflow-auto bg-white text-sm text-zinc-900 dark:bg-zinc-950 dark:text-zinc-100">
                <HighlightAuto code={decryptedContent ?? ''} {languageNames} langtag={Boolean(languageNames?.length)} let:highlighted>
                    <LineNumbers {highlighted} hideBorder />
                </HighlightAuto>
            </div>
        </section>
    {/if}
</AppLayout>

<style>
    .paste-highlight :global(pre) {
        margin: 0;
        min-height: 100%;
    }

    .paste-highlight :global(code) {
        display: block;
        min-height: 100%;
    }

    .paste-highlight :global(td) {
        width: auto !important;
    }

    .paste-highlight :global(> div) {
        flex: 1 1 auto;
        min-height: 100%;
        display: flex;
        flex-direction: column;
    }

    .paste-highlight :global(table) {
        width: 100%;
        min-height: 100%;
        height: 100%;
        flex: 1 1 auto;
        align-self: stretch;
    }

    .paste-highlight :global(tbody.hljs) {
        height: calc(100vh - 111px);
    }
</style>