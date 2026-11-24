import type { Config } from 'ziggy-js';

/** What Ziggy accepts for route parameters: a single value, a list or a map. */
type RouteParameters = string | number | unknown[] | Record<string, unknown>;

declare global {
    function route(): Config;
    function route(name: string, params?: RouteParameters, absolute?: boolean): string;
}

declare module '@vue/runtime-core' {
    interface ComponentCustomProperties {
        route: typeof route;
    }
}
