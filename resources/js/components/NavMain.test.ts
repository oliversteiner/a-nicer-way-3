import { SidebarProvider } from '@/components/ui/sidebar';
import { mount } from '@vue/test-utils';
import { describe, expect, it, vi } from 'vitest';
import { defineComponent, h } from 'vue';
import NavMain from './NavMain.vue';

vi.mock('@inertiajs/vue3', () => ({
    usePage: () => ({ url: '/editor/journeys' }),
    // A link that renders its address, so the test can see where it points.
    Link: defineComponent({
        props: { href: { type: String, default: undefined } },
        setup:
            (props, { slots }) =>
            () =>
                h('a', { href: props.href }, slots.default?.()),
    }),
}));

const Icon = defineComponent({ render: () => h('svg') });

// The menu buttons need the sidebar context.
const mountMenu = (items: unknown[]) =>
    mount(defineComponent({ render: () => h(SidebarProvider, null, { default: () => h(NavMain, { items } as never) }) }));

describe('NavMain', () => {
    const items = [
        { title: 'Dashboard', href: '/dashboard', icon: Icon },
        { title: 'Journeys', href: '/editor/journeys', icon: Icon },
    ];

    it('links every item to its address', () => {
        const wrapper = mountMenu(items);

        expect(wrapper.findAll('a').map((a) => a.attributes('href'))).toEqual(['/dashboard', '/editor/journeys']);
    });

    it('marks only the item of the current page as active', () => {
        const wrapper = mountMenu(items);

        const active = wrapper.findAll('[data-active="true"]').map((el) => el.text());

        expect(active).toEqual(['Journeys']);
    });
});
