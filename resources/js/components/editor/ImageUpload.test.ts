import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';
import ImageUpload from './ImageUpload.vue';

function choose(wrapper: ReturnType<typeof mount>, files: File[]) {
    const input = wrapper.get('input[type=file]');
    Object.defineProperty(input.element, 'files', { value: files, configurable: true });
    return input.trigger('change');
}

describe('ImageUpload', () => {
    it('offers the supported image types', () => {
        const wrapper = mount(ImageUpload);

        expect(wrapper.get('input').attributes('accept')).toBe('image/png,image/jpeg,image/webp,image/svg+xml');
        expect(wrapper.text()).toContain('Upload');
    });

    it('uses the given label', () => {
        expect(mount(ImageUpload, { props: { label: 'Replace' } }).text()).toContain('Replace');
    });

    it('emits the chosen file', async () => {
        const wrapper = mount(ImageUpload);
        const file = new File(['x'], 'a.png', { type: 'image/png' });

        await choose(wrapper, [file]);

        expect(wrapper.emitted('select')).toEqual([[file]]);
    });

    it('emits nothing when no file was chosen', async () => {
        const wrapper = mount(ImageUpload);

        await choose(wrapper, []);

        expect(wrapper.emitted('select')).toBeUndefined();
    });

    it('can be disabled', () => {
        expect(
            mount(ImageUpload, { props: { disabled: true } })
                .get('input')
                .attributes('disabled'),
        ).toBeDefined();
    });
});
