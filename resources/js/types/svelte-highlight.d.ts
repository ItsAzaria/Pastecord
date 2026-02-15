declare module 'svelte-highlight' {
    import type { SvelteComponent } from 'svelte';
    import type { LanguageName } from 'svelte-highlight/languages';

    export class Highlight extends SvelteComponent<{
        code: string;
        language?: LanguageName;
        languageNames?: LanguageName[];
        langtag?: boolean;
    }> { }

    export class HighlightAuto extends SvelteComponent<{
        code: string;
        languageNames?: LanguageName[];
        langtag?: boolean;
    }> { }

    export class HighlightSvelte extends SvelteComponent<{
        code: string;
        langtag?: boolean;
    }> { }

    export class LineNumbers extends SvelteComponent<{
        highlighted: string;
        hideBorder?: boolean;
        'padding-right'?: string;
    }> { }
}
