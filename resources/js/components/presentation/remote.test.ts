import { usePresentationStore } from '@/stores/presentation';
import { useRemoteStore } from '@/stores/remote';
import { flushPromises, mount } from '@vue/test-utils';
import { createPinia, setActivePinia, type Pinia } from 'pinia';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import RemotePanel from './RemotePanel.vue';

vi.mock('@laravel/echo-vue', () => ({ echo: () => ({ channel: () => ({ listen: vi.fn() }), leave: vi.fn() }) }));
vi.mock('@/lib/remoteApi', async (importOriginal) => ({
    ...(await importOriginal<typeof import('@/lib/remoteApi')>()),
    pairPresentation: vi.fn().mockResolvedValue({ remote_token: 'rt', presenter_token: 'pt', remote_url: 'http://x.test/remote/rt' }),
    sendState: vi.fn().mockResolvedValue(null),
    endPairing: vi.fn().mockResolvedValue(null),
}));

let pinia: Pinia;

beforeEach(() => {
    pinia = createPinia();
    setActivePinia(pinia);
    sessionStorage.clear();
});

describe('RemotePanel', () => {
    it('pairs when it opens and shows the link, a QR code and the waiting status', async () => {
        const wrapper = mount(RemotePanel, { props: { slug: 'demo' }, global: { plugins: [pinia] } });
        await flushPromises();

        expect(wrapper.get('[data-test=remote-url]').text()).toBe('http://x.test/remote/rt');
        expect(wrapper.find('[role=img] svg').exists()).toBe(true);
        expect(wrapper.get('[data-test=remote-status]').text()).toContain('Wartet');
    });

    it('shows when a remote control has connected', async () => {
        const wrapper = mount(RemotePanel, { props: { slug: 'demo' }, global: { plugins: [pinia] } });
        await flushPromises();

        useRemoteStore().handleCommand({ action: 'hello', value: null });
        await flushPromises();

        expect(wrapper.get('[data-test=remote-status]').text()).toBe('Fernsteuerung verbunden');
    });

    it('closes through the panel close button', async () => {
        const store = usePresentationStore();
        store.togglePanel('remote');
        const wrapper = mount(RemotePanel, { props: { slug: 'demo' }, global: { plugins: [pinia] } });

        await wrapper.get('button[aria-label="Schliessen"]').trigger('click');

        expect(store.panels.remote).toBe(false);
    });
});
