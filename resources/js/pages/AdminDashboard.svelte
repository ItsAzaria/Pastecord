<script lang="ts">
    import AppLayout from '../layouts/AppLayout.svelte';
    import { page } from '@inertiajs/svelte';
    import { onDestroy, onMount } from 'svelte';
    import type { Chart, ChartConfiguration } from 'chart.js';

    type TrendPoint = {
        date: string;
        label: string;
        users: number;
        pastes: number;
    };

    type TopLanguage = {
        language: string;
        total: number;
    };

    type AnalyticsPayload = {
        kpis: {
            total_users: number;
            total_pastes: number;
            new_users_24h: number;
            new_pastes_24h: number;
            active_pastes: number;
            encrypted_pastes: number;
            burn_after_read_pastes: number;
            total_reads: number;
        };
        trend: TrendPoint[];
        top_languages: TopLanguage[];
    };

    const numberFormatter = new Intl.NumberFormat('en-US');
    const formatNumber = (value: number) => numberFormatter.format(value);
    const fallbackAnalytics: AnalyticsPayload = {
        kpis: {
            total_users: 0,
            total_pastes: 0,
            new_users_24h: 0,
            new_pastes_24h: 0,
            active_pastes: 0,
            encrypted_pastes: 0,
            burn_after_read_pastes: 0,
            total_reads: 0,
        },
        trend: [],
        top_languages: [],
    };

    const sumTrend = (trend: TrendPoint[], key: 'users' | 'pastes') => trend.reduce((carry, point) => carry + point[key], 0);

    const getCsrfToken = () => (typeof document !== 'undefined' ? document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') : null);

    let pasteKey = '';
    let message = '';
    let error = '';
    let isDeleting = false;
    let isTriggeringException = false;
    let usersTrendCanvas: HTMLCanvasElement | null = null;
    let pastesTrendCanvas: HTMLCanvasElement | null = null;
    let usersChart: Chart | null = null;
    let pastesChart: Chart | null = null;
    let isMounted = false;

    $: analytics = ($page.props.analytics as AnalyticsPayload | undefined) ?? fallbackAnalytics;
    $: kpis = analytics.kpis;
    $: trend = analytics.trend;
    $: topLanguages = analytics.top_languages;
    $: userTrendValues = trend.map((point) => point.users);
    $: pasteTrendValues = trend.map((point) => point.pastes);
    $: trendLabels = trend.map((point) => point.label);
    $: trendKey = trend.map((point) => `${point.date}:${point.users}:${point.pastes}`).join('|');
    $: maxLanguageCount = Math.max(...topLanguages.map((item) => item.total), 1);
    $: recentUsers = sumTrend(trend, 'users');
    $: recentPastes = sumTrend(trend, 'pastes');
    $: encryptedPercent = kpis.total_pastes > 0 ? Math.round((kpis.encrypted_pastes / kpis.total_pastes) * 100) : 0;
    $: burnAfterReadPercent = kpis.total_pastes > 0 ? Math.round((kpis.burn_after_read_pastes / kpis.total_pastes) * 100) : 0;

    const destroyCharts = () => {
        usersChart?.destroy();
        pastesChart?.destroy();
        usersChart = null;
        pastesChart = null;
    };

    const buildChartConfig = (label: string, data: number[], color: string): ChartConfiguration<'line'> => ({
        type: 'line',
        data: {
            labels: trendLabels,
            datasets: [
                {
                    label,
                    data,
                    borderColor: color,
                    borderWidth: 2,
                    pointRadius: 2,
                    pointHoverRadius: 3,
                    fill: false,
                    tension: 0.35,
                },
            ],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false,
                },
            },
            scales: {
                x: {
                    ticks: {
                        maxRotation: 0,
                        autoSkip: true,
                    },
                    grid: {
                        display: false,
                    },
                },
                y: {
                    beginAtZero: true,
                    ticks: {
                        precision: 0,
                    },
                },
            },
        },
    });

    const renderCharts = async () => {
        if (!isMounted || !usersTrendCanvas || !pastesTrendCanvas || trend.length === 0) {
            destroyCharts();
            return;
        }

        const { default: ChartJs } = await import('chart.js/auto');
        const styles = getComputedStyle(document.documentElement);
        const indigoColor = styles.getPropertyValue('--color-indigo-500').trim() || '#6366f1';
        const emeraldColor = styles.getPropertyValue('--color-emerald-500').trim() || '#10b981';

        destroyCharts();
        usersChart = new ChartJs(usersTrendCanvas, buildChartConfig('User signups', userTrendValues, indigoColor));
        pastesChart = new ChartJs(pastesTrendCanvas, buildChartConfig('Pastes created', pasteTrendValues, emeraldColor));
    };

    onMount(() => {
        isMounted = true;
        void renderCharts();
    });

    onDestroy(() => {
        isMounted = false;
        destroyCharts();
    });

    $: if (isMounted && typeof trendKey === 'string') {
        void renderCharts();
    }

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
            <p class="text-sm text-zinc-500">Platform health, growth, and moderation controls.</p>
        </div>

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                <p class="text-xs uppercase tracking-wide text-zinc-500">Total users</p>
                <p class="mt-2 text-2xl font-semibold text-zinc-900 dark:text-zinc-100">{formatNumber(kpis.total_users)}</p>
                <p class="mt-1 text-xs text-zinc-500">+{formatNumber(kpis.new_users_24h)} in last 24h</p>
            </div>

            <div class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                <p class="text-xs uppercase tracking-wide text-zinc-500">Total pastes</p>
                <p class="mt-2 text-2xl font-semibold text-zinc-900 dark:text-zinc-100">{formatNumber(kpis.total_pastes)}</p>
                <p class="mt-1 text-xs text-zinc-500">+{formatNumber(kpis.new_pastes_24h)} in last 24h</p>
            </div>

            <div class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                <p class="text-xs uppercase tracking-wide text-zinc-500">Active pastes</p>
                <p class="mt-2 text-2xl font-semibold text-zinc-900 dark:text-zinc-100">{formatNumber(kpis.active_pastes)}</p>
                <p class="mt-1 text-xs text-zinc-500">Not expired right now</p>
            </div>

            <div class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                <p class="text-xs uppercase tracking-wide text-zinc-500">Total reads</p>
                <p class="mt-2 text-2xl font-semibold text-zinc-900 dark:text-zinc-100">{formatNumber(kpis.total_reads)}</p>
                <p class="mt-1 text-xs text-zinc-500">Across all pastes</p>
            </div>
        </div>

        <div class="grid gap-4 xl:grid-cols-3">
            <div class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900 xl:col-span-2">
                <div class="flex items-center justify-between">
                    <h2 class="text-lg font-medium">14-day creation trends</h2>
                    <p class="text-xs text-zinc-500">Users: {formatNumber(recentUsers)} • Pastes: {formatNumber(recentPastes)}</p>
                </div>

                {#if trend.length === 0}
                    <div
                        class="mt-4 rounded-lg border border-dashed border-zinc-200 px-4 py-6 text-center text-sm text-zinc-500 dark:border-zinc-800"
                    >
                        No trend data yet.
                    </div>
                {:else}
                    <div class="mt-5 space-y-5">
                        <div>
                            <div class="mb-2 flex items-center justify-between text-xs text-zinc-500">
                                <span>User signups</span>
                                <span>{formatNumber(Math.max(...userTrendValues, 0))} max/day</span>
                            </div>
                            <div class="h-28 w-full">
                                <canvas bind:this={usersTrendCanvas}></canvas>
                            </div>
                        </div>

                        <div>
                            <div class="mb-2 flex items-center justify-between text-xs text-zinc-500">
                                <span>Pastes created</span>
                                <span>{formatNumber(Math.max(...pasteTrendValues, 0))} max/day</span>
                            </div>
                            <div class="h-28 w-full">
                                <canvas bind:this={pastesTrendCanvas}></canvas>
                            </div>
                        </div>
                    </div>
                {/if}
            </div>

            <div class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                <h2 class="text-lg font-medium">Usage breakdown</h2>
                <div class="mt-4 grid grid-cols-2 gap-3 text-sm">
                    <div class="rounded-lg border border-zinc-200 p-3 dark:border-zinc-800">
                        <p class="text-xs uppercase tracking-wide text-zinc-500">Encrypted</p>
                        <p class="mt-1 text-lg font-semibold text-zinc-900 dark:text-zinc-100">{encryptedPercent}%</p>
                    </div>
                    <div class="rounded-lg border border-zinc-200 p-3 dark:border-zinc-800">
                        <p class="text-xs uppercase tracking-wide text-zinc-500">Burn after read</p>
                        <p class="mt-1 text-lg font-semibold text-zinc-900 dark:text-zinc-100">{burnAfterReadPercent}%</p>
                    </div>
                </div>

                <div class="mt-5">
                    <h3 class="text-sm font-medium text-zinc-700 dark:text-zinc-200">Top languages</h3>
                    {#if topLanguages.length === 0}
                        <p class="mt-2 text-sm text-zinc-500">No language data available.</p>
                    {:else}
                        <div class="mt-3 space-y-3">
                            {#each topLanguages as item (item.language)}
                                <div>
                                    <div class="mb-1 flex items-center justify-between text-xs text-zinc-500">
                                        <span>{item.language}</span>
                                        <span>{formatNumber(item.total)}</span>
                                    </div>
                                    <div class="h-2 w-full rounded-full bg-zinc-100 dark:bg-zinc-800">
                                        <div
                                            class="h-2 rounded-full bg-indigo-500"
                                            style={`width: ${Math.max((item.total / maxLanguageCount) * 100, 4)}%`}
                                        ></div>
                                    </div>
                                </div>
                            {/each}
                        </div>
                    {/if}
                </div>
            </div>
        </div>

        <div class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
            <div class="mb-4">
                <h2 class="text-lg font-medium">Moderation tools</h2>
                <p class="text-sm text-zinc-500">Delete any paste by key and run error-log checks.</p>
            </div>
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
