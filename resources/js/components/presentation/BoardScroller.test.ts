import type { Backdrop, Board } from '@/types/presentation';
import { mount } from '@vue/test-utils';
import { createPinia, setActivePinia } from 'pinia';
import { beforeEach, describe, expect, it } from 'vitest';
import BoardScroller from './BoardScroller.vue';

const board = (position: number): Board => ({
    id: position,
    position,
    title: `B${position}`,
    description: null,
    notes: null,
    day: 1,
    time: null,
    location: null,
    is_night: false,
    mood: null,
    simulator_state: null,
    assets: [],
});

const backdrop = (id: number, extra: Partial<Backdrop> = {}): Backdrop => ({
    id,
    layer: 'back',
    url: `/backdrops/${id}`,
    speed: 0.3,
    align: 'bottom',
    height: 100,
    repeat: true,
    ...extra,
});

const mountScroller = (backdrops: Backdrop[]) =>
    mount(BoardScroller, { props: { boards: [board(1), board(2)], backdrops }, global: { plugins: [createPinia()] } });

beforeEach(() => setActivePinia(createPinia()));

describe('BoardScroller backdrops', () => {
    it('shows nothing extra without backdrops', () => {
        expect(mountScroller([]).findAll('[data-test=backdrop]')).toHaveLength(0);
    });

    it('draws a backdrop as an image that repeats along the journey', () => {
        const wrapper = mountScroller([backdrop(1)]);

        const style = wrapper.get('[data-test=backdrop]').attributes('style');

        expect(style).toContain('background-image: url("/backdrops/1")');
        expect(style).toContain('background-repeat: repeat-x');
        expect(style).toContain('background-size: auto 100%');
        expect(style).toContain('height: 100%');
        expect(style).toContain('bottom: 0');
    });

    it('does not repeat a backdrop that is set not to', () => {
        const wrapper = mountScroller([backdrop(1, { repeat: false })]);

        expect(wrapper.get('[data-test=backdrop]').attributes('style')).toContain('background-repeat: no-repeat');
    });

    it('places a backdrop at the top, the centre or the bottom', () => {
        const top = mountScroller([backdrop(1, { align: 'top', height: 40 })])
            .get('[data-test=backdrop]')
            .attributes('style');
        const centre = mountScroller([backdrop(2, { align: 'center' })])
            .get('[data-test=backdrop]')
            .attributes('style');

        expect(top).toContain('top: 0');
        expect(top).toContain('height: 40%');
        expect(centre).toContain('top: 50%');
        expect(centre).toContain('translateY(-50%)');
    });

    it('puts back backdrops behind the boards and front backdrops in front of them', () => {
        const wrapper = mountScroller([backdrop(1, { layer: 'front', speed: 1.5 }), backdrop(2)]);

        const order = [
            ...wrapper.element.querySelectorAll(
                '[data-test=backdrop], [data-layer=back]:not([data-test]), [data-layer=middle], [data-layer=front]:not([data-test])',
            ),
        ].map((el) =>
            el.getAttribute('data-test') === 'backdrop' ? `backdrop-${el.getAttribute('data-layer')}` : `boards-${el.getAttribute('data-layer')}`,
        );

        expect(order).toEqual(['backdrop-back', 'boards-back', 'boards-middle', 'boards-front', 'backdrop-front']);
    });
});
