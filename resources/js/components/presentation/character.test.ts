import { usePresentationStore } from '@/stores/presentation';
import type { Character } from '@/types/presentation';
import { mount } from '@vue/test-utils';
import { createPinia, setActivePinia, type Pinia } from 'pinia';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import CharacterLayer from './CharacterLayer.vue';
import CharacterPanel from './CharacterPanel.vue';

const kevin: Character = { id: 1, name: 'Kevin', images: { idle: '/character-images/1', go: '/character-images/2' } };
const bus: Character = { id: 2, name: 'Bus', images: {} };

let pinia: Pinia;

beforeEach(() => {
    pinia = createPinia();
    setActivePinia(pinia);
});

describe('CharacterLayer', () => {
    it('shows nothing without a character', () => {
        const wrapper = mount(CharacterLayer, { props: { characters: [] }, global: { plugins: [pinia] } });

        expect(wrapper.find('[data-test=character]').exists()).toBe(false);
    });

    it('shows the image of the current pose', async () => {
        const store = usePresentationStore();
        store.setCharacters([1, 2]);
        const wrapper = mount(CharacterLayer, { props: { characters: [kevin, bus] }, global: { plugins: [pinia] } });
        expect(wrapper.get('img').attributes('src')).toBe('/character-images/1');

        store.setPose('go');
        await wrapper.vm.$nextTick();

        expect(wrapper.get('img').attributes('src')).toBe('/character-images/2');
        expect(wrapper.get('img').attributes('alt')).toBe('Kevin geht');
    });

    it('falls back to the standing image when a pose has none', async () => {
        const store = usePresentationStore();
        store.setCharacters([1]);
        const wrapper = mount(CharacterLayer, { props: { characters: [kevin] }, global: { plugins: [pinia] } });

        store.setPose('pause');
        await wrapper.vm.$nextTick();

        expect(wrapper.get('img').attributes('src')).toBe('/character-images/1');
    });

    it('shows a labelled placeholder for a character without images', () => {
        const store = usePresentationStore();
        store.setCharacters([2]);
        const wrapper = mount(CharacterLayer, { props: { characters: [bus] }, global: { plugins: [pinia] } });

        const placeholder = wrapper.get('[data-test=character-placeholder]');

        expect(placeholder.text()).toContain('Bus');
        expect(placeholder.attributes('aria-label')).toBe('Bus steht');
        expect(wrapper.find('img').exists()).toBe(false);
    });

    it('moves only for go and greet, and only if motion is welcome', async () => {
        const store = usePresentationStore();
        store.setCharacters([2]);
        const wrapper = mount(CharacterLayer, { props: { characters: [bus] }, global: { plugins: [pinia] } });
        const classes = () => wrapper.get('[data-test=character-placeholder]').classes();
        expect(classes().some((c) => c.includes('animate'))).toBe(false);

        store.setPose('go');
        await wrapper.vm.$nextTick();
        expect(classes()).toContain('motion-safe:animate-bounce');

        store.setPose('pause');
        await wrapper.vm.$nextTick();
        expect(classes().some((c) => c.includes('animate'))).toBe(false);
    });
});

describe('CharacterPanel', () => {
    beforeEach(() => vi.useFakeTimers());
    afterEach(() => vi.useRealTimers());

    it('explains when the journey has no characters', () => {
        const wrapper = mount(CharacterPanel, { props: { characters: [] }, global: { plugins: [pinia] } });

        expect(wrapper.get('[data-test=character-panel]').text()).toContain('keine Figuren');
        expect(wrapper.find('button[aria-pressed]').exists()).toBe(false);
    });

    it('selects a character and marks it', async () => {
        const store = usePresentationStore();
        store.setCharacters([1, 2]);
        const wrapper = mount(CharacterPanel, { props: { characters: [kevin, bus] }, global: { plugins: [pinia] } });
        const buttons = wrapper.findAll('[aria-label="Figur wählen"] button');
        expect(buttons.map((b) => b.attributes('aria-pressed'))).toEqual(['true', 'false']);

        await buttons[1].trigger('click');

        expect(store.characterId).toBe(2);
        expect(wrapper.findAll('[aria-label="Figur wählen"] button').map((b) => b.attributes('aria-pressed'))).toEqual(['false', 'true']);
    });

    it('sets the pose and marks the active one', async () => {
        const store = usePresentationStore();
        store.setCharacters([1]);
        const wrapper = mount(CharacterPanel, { props: { characters: [kevin] }, global: { plugins: [pinia] } });
        const pressed = () =>
            Object.fromEntries(wrapper.findAll('[aria-label="Aktion"] button').map((b) => [b.text(), b.attributes('aria-pressed')]));
        expect(pressed()).toEqual({ Stehen: 'true', Gehen: 'false', Pause: 'false', Grüssen: 'false' });

        await wrapper.findAll('[aria-label="Aktion"] button')[1].trigger('click');

        expect(store.characterPose).toBe('go');
        expect(pressed().Gehen).toBe('true');
    });

    it('shows the greeting briefly, then the figure stands again', async () => {
        const store = usePresentationStore();
        store.setCharacters([1]);
        const wrapper = mount(CharacterPanel, { props: { characters: [kevin] }, global: { plugins: [pinia] } });

        await wrapper.findAll('[aria-label="Aktion"] button')[3].trigger('click');
        expect(store.characterPose).toBe('greet');
        vi.advanceTimersByTime(2600);
        await wrapper.vm.$nextTick();

        expect(wrapper.findAll('[aria-label="Aktion"] button')[0].attributes('aria-pressed')).toBe('true');
    });

    it('closes through the panel close button', async () => {
        const store = usePresentationStore();
        store.togglePanel('character');
        const wrapper = mount(CharacterPanel, { props: { characters: [kevin] }, global: { plugins: [pinia] } });

        await wrapper.get('button[aria-label="Schliessen"]').trigger('click');

        expect(store.panels.character).toBe(false);
    });
});
