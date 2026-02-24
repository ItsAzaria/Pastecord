import { createInertiaApp, type ResolvedComponent } from '@inertiajs/svelte';
import { hydrate, mount } from 'svelte';
import '../css/app.css';
import './bootstrap';

createInertiaApp({
    resolve: (name: string) => {
        const pages = import.meta.glob<ResolvedComponent>('./pages/**/*.svelte', { eager: true });
        return pages[`./pages/${name}.svelte`];
    },
    setup({ el, App, props }) {
        if (el && el.dataset.serverRendered === 'true') {
            hydrate(App, { target: el, props });
        } else if (el) {
            mount(App, { target: el, props });
        }
    },
});

// Suppress Monaco Editor errors for now - https://github.com/esm-dev/modern-monaco/issues/56
window.addEventListener('error', function (event) {
    if (event.message && event.message.includes("Could not find source file: 'file:///paste.txt'")) {
        event.preventDefault();
    }
});

window.addEventListener('unhandledrejection', function (event) {
    if (event.reason && event.reason.message && event.reason.message.includes("Could not find source file: 'file:///paste.txt'")) {
        event.preventDefault();
    }
});
