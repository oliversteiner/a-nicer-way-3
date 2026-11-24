import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { effectScope } from 'vue';
import { useCountdown } from './useCountdown';

function setup(options: Parameters<typeof useCountdown>[0] = {}) {
    const scope = effectScope();
    const countdown = scope.run(() => useCountdown(options))!;
    return { countdown, scope };
}

describe('useCountdown', () => {
    beforeEach(() => vi.useFakeTimers());
    afterEach(() => vi.useRealTimers());

    it('shows the duration as mm:ss', () => {
        const { countdown } = setup({ seconds: 125 });

        expect(countdown.display.value).toBe('02:05');
        expect(countdown.running.value).toBe(false);
    });

    it('counts down while running', () => {
        const { countdown } = setup({ seconds: 10 });

        countdown.start();
        vi.advanceTimersByTime(3000);

        expect(countdown.remaining.value).toBe(7);
        expect(countdown.running.value).toBe(true);
    });

    it('keeps the remaining time on pause and continues from there', () => {
        const { countdown } = setup({ seconds: 10 });

        countdown.start();
        vi.advanceTimersByTime(4000);
        countdown.pause();
        vi.advanceTimersByTime(5000);
        expect(countdown.remaining.value).toBe(6);

        countdown.start();
        vi.advanceTimersByTime(2000);
        expect(countdown.remaining.value).toBe(4);
    });

    it('finishes once, calls onFinish and reports time out', () => {
        const onFinish = vi.fn();
        const { countdown } = setup({ seconds: 3, onFinish });

        countdown.start();
        vi.advanceTimersByTime(10000);

        expect(countdown.remaining.value).toBe(0);
        expect(countdown.running.value).toBe(false);
        expect(countdown.timeOut.value).toBe(true);
        expect(onFinish).toHaveBeenCalledTimes(1);
    });

    it('restarts from the full duration after it ran out', () => {
        const { countdown } = setup({ seconds: 2 });

        countdown.start();
        vi.advanceTimersByTime(5000);
        countdown.start();

        expect(countdown.remaining.value).toBe(2);
        expect(countdown.timeOut.value).toBe(false);
        expect(countdown.running.value).toBe(true);
    });

    it('resets to the duration', () => {
        const { countdown } = setup({ seconds: 30 });

        countdown.start();
        vi.advanceTimersByTime(10000);
        countdown.reset();

        expect(countdown.remaining.value).toBe(30);
        expect(countdown.running.value).toBe(false);
    });

    it('flags the last seconds as near the end, but not for short timers', () => {
        const { countdown } = setup({ seconds: 90, warningSeconds: 60 });
        countdown.start();

        vi.advanceTimersByTime(20000);
        expect(countdown.nearEnd.value).toBe(false);
        vi.advanceTimersByTime(15000);
        expect(countdown.nearEnd.value).toBe(true);

        const short = setup({ seconds: 30, warningSeconds: 60 }).countdown;
        short.start();
        expect(short.nearEnd.value).toBe(false);
    });

    it('clamps new durations and stops a running countdown', () => {
        const { countdown } = setup({ seconds: 10 });
        countdown.start();

        countdown.setDuration(-5);
        expect(countdown.total.value).toBe(0);
        expect(countdown.running.value).toBe(false);

        countdown.setDuration(10_000_000);
        expect(countdown.total.value).toBe(999 * 60 + 59);

        countdown.setDuration(Number.NaN);
        expect(countdown.total.value).toBe(0);
    });

    it('does not start with a duration of zero', () => {
        const { countdown } = setup({ seconds: 0 });

        countdown.start();

        expect(countdown.running.value).toBe(false);
    });

    it('stops ticking when its scope is disposed', () => {
        const { countdown, scope } = setup({ seconds: 10 });
        countdown.start();

        scope.stop();
        vi.advanceTimersByTime(5000);

        expect(countdown.remaining.value).toBe(10);
    });
});
