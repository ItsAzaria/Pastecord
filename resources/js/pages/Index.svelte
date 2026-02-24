<script lang="ts">
    import { onDestroy, onMount } from 'svelte';
    import { init } from 'modern-monaco';
    import { router } from '@inertiajs/svelte';
    import AppLayout from '../layouts/AppLayout.svelte';
    import { encrypt, encryptWithPassword } from '../lib/crypto';
    import { allLanguages, highlightLanguages } from '../lib/highlightLanguages';
    import { editor } from 'modern-monaco/editor-core';
    import hljs from 'highlight.js';

    let isMenuOpen = false;
    let language = 'auto';
    let isEncrypted = false;
    let burnAfterRead = false;
    let password = '';
    let content = '';

    let monacoInstance: Awaited<ReturnType<typeof init>>;
    let monacoModel: editor.ITextModel;
    let codeEditor: editor.IStandaloneCodeEditor;

    let initialized = false;

    onMount(async () => {
        loadDraft();

        monacoInstance = await init({
            themes: ['one-light', 'one-dark-pro'],
        });

        const uri = monacoInstance.Uri.parse('file:///paste.txt');
        let model = monacoInstance.editor.getModel(uri);
        if (!model) {
            monacoModel = monacoInstance.editor.createModel(content, language === 'auto' ? 'plaintext' : language, uri);
        } else {
            monacoModel = model;
        }

        codeEditor = monacoInstance.editor.create(document.getElementById('monaco-editor')!, {
            padding: {
                top: 32,
            },
            model: monacoModel,
            formatOnPaste: true,
        });

        codeEditor.focus();

        codeEditor.onDidChangeModelContent(() => {
            content = codeEditor.getValue();
        });

        updateMonacoTheme();

        observer.observe(document.documentElement, {
            attributes: true,
            attributeFilter: ['class'],
        });

        window.addEventListener('keydown', handler);

        initialized = true;
    });

    onDestroy(() => {
        window.removeEventListener('keydown', handler);
        observer.disconnect();
        codeEditor?.dispose();
        monacoModel?.dispose();
    });

    const observer = new MutationObserver(updateMonacoTheme);

    const handler = (event: KeyboardEvent) => {
        if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 's') {
            event.preventDefault();
            handleSave();
        }
    };

    $: if (initialized && language && content) {
        updateMonacoLanguage();
    }

    function updateMonacoTheme() {
        const isDark = document.documentElement.classList.contains('dark');
        monacoInstance.editor.setTheme(isDark ? 'one-dark-pro' : 'one-light');
    }

    function updateMonacoLanguage() {
        const lang = getLanguage();

        monacoInstance.editor.setModelLanguage(monacoModel, lang);
    }

    function getLanguage() {
        if (language !== 'auto') return language;

        const result = hljs.highlightAuto(content, highlightLanguages);

        return result.language || 'plaintext';
    }

    let expiresInDays: string | number = '';
    let expiresInHours: string | number = '';
    let expiresInMinutes: string | number = '';
    let isSaving = false;
    let saveError = '';
    let saveKey = '';
    let clientKey = '';
    let draftSaveTimeout: ReturnType<typeof setTimeout> | null = null;

    type DraftPayload = {
        content: string;
        language: string;
        isEncrypted: boolean;
        burnAfterRead: boolean;
        expiresInDays: string | number;
        expiresInHours: string | number;
        expiresInMinutes: string | number;
    };

    const DRAFT_STORAGE_KEY = 'pastecord:draft';

    const languageOptions = allLanguages;

    const toggleMenu = () => {
        isMenuOpen = !isMenuOpen;
    };

    const closeMenu = () => {
        isMenuOpen = false;
    };

    const clamp = (value: string | number, min: number, max: number) => {
        if (value === '') return '';
        const numeric = Number(value);
        if (Number.isNaN(numeric)) return '';
        return Math.min(Math.max(numeric, min), max);
    };

    const getCsrfToken = () => (typeof document !== 'undefined' ? document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') : null);

    const getExpiresInSeconds = () => {
        const days = Number(expiresInDays) || 0;
        const hours = Number(expiresInHours) || 0;
        const minutes = Number(expiresInMinutes) || 0;
        return Math.max(0, days * 86400 + hours * 3600 + minutes * 60);
    };

    const loadDraft = () => {
        if (typeof localStorage === 'undefined') return;
        const raw = localStorage.getItem(DRAFT_STORAGE_KEY);
        if (!raw) return;
        try {
            const draft = JSON.parse(raw) as Partial<DraftPayload>;
            content = typeof draft.content === 'string' ? draft.content : content;
            language = typeof draft.language === 'string' ? draft.language : language;
            isEncrypted = typeof draft.isEncrypted === 'boolean' ? draft.isEncrypted : isEncrypted;
            burnAfterRead = typeof draft.burnAfterRead === 'boolean' ? draft.burnAfterRead : burnAfterRead;
            expiresInDays = draft.expiresInDays ?? expiresInDays;
            expiresInHours = draft.expiresInHours ?? expiresInHours;
            expiresInMinutes = draft.expiresInMinutes ?? expiresInMinutes;
        } catch {
            localStorage.removeItem(DRAFT_STORAGE_KEY);
        }
    };

    const clearDraft = () => {
        if (typeof localStorage === 'undefined') return;
        localStorage.removeItem(DRAFT_STORAGE_KEY);
    };

    const saveDraft = (draft: DraftPayload | null) => {
        if (typeof localStorage === 'undefined' || !draft) return;
        if (!draft.content.trim()) {
            clearDraft();
            return;
        }
        localStorage.setItem(DRAFT_STORAGE_KEY, JSON.stringify(draft));
    };

    const scheduleDraftSave = (draft: DraftPayload) => {
        if (typeof window === 'undefined') return;
        if (draftSaveTimeout) {
            clearTimeout(draftSaveTimeout);
        }
        draftSaveTimeout = setTimeout(() => saveDraft(draft), 300);
    };

    const handleSave = async () => {
        if (isSaving) return;
        saveError = '';
        saveKey = '';
        clientKey = '';

        if (!content.trim()) {
            saveError = 'Paste content is empty.';
            return;
        }

        if (password.trim() && !isEncrypted) {
            saveError = 'Enable encryption to use a password.';
            return;
        }

        isSaving = true;

        try {
            let payloadContent = content;
            let initVector: string | null = null;
            let salt: string | null = null;
            let passwordProtected = false;

            if (isEncrypted) {
                if (password.trim()) {
                    const encrypted = await encryptWithPassword(content, password.trim());
                    payloadContent = encrypted.ciphertext;
                    initVector = encrypted.iv;
                    salt = encrypted.salt ?? null;
                    passwordProtected = true;
                } else {
                    const encrypted = await encrypt(content);
                    payloadContent = encrypted.ciphertext;
                    initVector = encrypted.iv;
                    clientKey = encrypted.key ?? '';
                }
            }

            const expiresInSeconds = getExpiresInSeconds();
            const csrfToken = getCsrfToken();

            const response = await fetch('/pastes', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    ...(csrfToken ? { 'X-CSRF-TOKEN': csrfToken } : {}),
                },
                body: JSON.stringify({
                    content: payloadContent,
                    encrypted: isEncrypted,
                    password_protected: passwordProtected,
                    init_vector: initVector,
                    salt,
                    language,
                    burn_after_read: burnAfterRead,
                    expires_in_seconds: expiresInSeconds > 0 ? expiresInSeconds : null,
                }),
            });

            if (!response.ok) {
                const errorPayload = await response.json().catch(() => null);
                saveError = errorPayload?.message ?? 'Failed to save paste.';
                return;
            }

            const data = await response.json().catch(() => null);
            saveKey = data?.key ?? '';
            if (saveKey) {
                clearDraft();
                const fragment = clientKey ? `#key=${encodeURIComponent(clientKey)}` : '';
                router.visit(`/${saveKey}${fragment}`, { replace: true });
            }
        } catch (error) {
            saveError = error instanceof Error ? error.message : 'Failed to save paste.';
        } finally {
            isSaving = false;
        }
    };

    $: expiresInDays = clamp(expiresInDays, 0, 365);
    $: expiresInHours = clamp(expiresInHours, 0, 23);
    $: expiresInMinutes = clamp(expiresInMinutes, 0, 59);
    $: if (password.trim() && !isEncrypted) {
        isEncrypted = true;
    }

    $: scheduleDraftSave({
        content,
        language,
        isEncrypted,
        burnAfterRead,
        expiresInDays,
        expiresInHours,
        expiresInMinutes,
    });
</script>

<AppLayout mainClass="flex-1 w-full px-0 py-0 h-full min-h-0">
    <div class="flex h-full w-full min-h-0">
        <div class="relative flex h-full min-h-0 flex-1">
            <div class="h-[calc(100vh-4rem)] w-full flex-1 min-h-0">
                <monaco-editor id="monaco-editor" class="block h-full"></monaco-editor>
            </div>

            <button
                type="button"
                on:click={toggleMenu}
                class="absolute right-2 top-4 rounded-full bg-zinc-900 p-2 text-white shadow-md transition hover:bg-zinc-800 dark:bg-zinc-100 dark:text-zinc-900 dark:hover:bg-white md:hidden"
                aria-label={isMenuOpen ? 'Close menu' : 'Open menu'}
            >
                {#if isMenuOpen}
                    <svg viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                        <line x1="6" y1="6" x2="18" y2="18" />
                        <line x1="18" y1="6" x2="6" y2="18" />
                    </svg>
                {:else}
                    <svg viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                        <line x1="3" y1="6" x2="21" y2="6" />
                        <line x1="3" y1="12" x2="21" y2="12" />
                        <line x1="3" y1="18" x2="21" y2="18" />
                    </svg>
                {/if}
            </button>
        </div>

        <aside
            class={`h-[calc(100vh-4rem)] shrink-0 overflow-hidden border-l border-zinc-200 bg-white p-6 transition-all duration-300 dark:border-zinc-800 dark:bg-zinc-950 md:w-80 md:opacity-100 ${
                isMenuOpen ? 'w-80 opacity-100' : 'w-0 opacity-0'
            }`}
        >
            <div class="flex items-center justify-between">
                <button type="button" on:click={closeMenu} class="text-sm text-zinc-500 hover:text-zinc-800 dark:hover:text-zinc-200 md:hidden">
                    Close
                </button>
            </div>

            <div class="mt-6 space-y-6">
                <button
                    type="button"
                    on:click={handleSave}
                    class="w-full rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-indigo-500 disabled:cursor-not-allowed disabled:opacity-70"
                    disabled={isSaving}
                >
                    {isSaving ? 'Saving...' : 'Save'}
                </button>

                {#if saveError}
                    <p class="text-sm text-red-600">{saveError}</p>
                {/if}

                <div class="space-y-2">
                    <label for="language" class="text-sm font-medium">Language</label>
                    <select
                        id="language"
                        bind:value={language}
                        class="w-full rounded-md border border-zinc-200 bg-white px-3 py-2 text-sm dark:border-zinc-800 dark:bg-zinc-950"
                    >
                        <option value="auto">Auto Detect</option>
                        {#each languageOptions as option (option.value)}
                            <option value={option.value}>{option.name}</option>
                        {/each}
                    </select>
                </div>

                <label class="flex items-center justify-between gap-3 text-sm">
                    <span class="font-medium">Encrypted?</span>
                    <input type="checkbox" bind:checked={isEncrypted} class="h-4 w-4 accent-[#5865F2]" />
                </label>

                <div class="space-y-2">
                    <label for="password" class="text-sm font-medium">Password (optional)</label>
                    <input
                        id="password"
                        type="password"
                        bind:value={password}
                        placeholder="Enter password"
                        class="w-full rounded-md border border-zinc-200 bg-white px-3 py-2 text-sm dark:border-zinc-800 dark:bg-zinc-950"
                    />
                </div>

                <label class="flex items-center justify-between gap-3 text-sm">
                    <span class="font-medium">Burn after read?</span>
                    <input type="checkbox" bind:checked={burnAfterRead} class="h-4 w-4 accent-[#5865F2]" />
                </label>

                <fieldset class="space-y-2">
                    <legend class="text-sm font-medium">Expires in</legend>
                    <div class="flex items-center gap-2">
                        <div class="flex items-center gap-2">
                            <input
                                type="number"
                                min="0"
                                max="365"
                                bind:value={expiresInDays}
                                class="no-spinner w-16 rounded-md border border-zinc-200 bg-white px-3 py-2 text-sm dark:border-zinc-800 dark:bg-zinc-950"
                            />
                            <span class="text-xs text-zinc-500">D</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <input
                                type="number"
                                min="0"
                                max="23"
                                bind:value={expiresInHours}
                                class="no-spinner w-16 rounded-md border border-zinc-200 bg-white px-3 py-2 text-sm dark:border-zinc-800 dark:bg-zinc-950"
                            />
                            <span class="text-xs text-zinc-500">H</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <input
                                type="number"
                                min="0"
                                max="59"
                                bind:value={expiresInMinutes}
                                class="no-spinner w-16 rounded-md border border-zinc-200 bg-white px-3 py-2 text-sm dark:border-zinc-800 dark:bg-zinc-950"
                            />
                            <span class="text-xs text-zinc-500">M</span>
                        </div>
                    </div>
                </fieldset>
            </div>
        </aside>
    </div>
</AppLayout>
