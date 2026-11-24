import type { Pairing, RemoteAction, RemoteState } from '@/types/remote';

export class RemoteApiError extends Error {
    constructor(public status: number) {
        super(`Remote request failed with status ${status}`);
    }
}

function xsrfToken(): string {
    const match = document.cookie.match(/(?:^|; )XSRF-TOKEN=([^;]+)/);
    return match ? decodeURIComponent(match[1]) : '';
}

async function request<T>(method: 'POST' | 'DELETE', url: string, body?: unknown): Promise<T | null> {
    const response = await fetch(url, {
        method,
        credentials: 'same-origin',
        headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-XSRF-TOKEN': xsrfToken(),
        },
        body: body === undefined ? undefined : JSON.stringify(body),
    });

    if (!response.ok) throw new RemoteApiError(response.status);

    return response.status === 204 ? null : ((await response.json()) as T);
}

export const pairPresentation = (slug: string) => request<Pairing>('POST', `/journeys/${encodeURIComponent(slug)}/remote`) as Promise<Pairing>;

export const sendCommand = (token: string, action: RemoteAction, value?: number) =>
    request<never>('POST', `/remote/${encodeURIComponent(token)}/command`, value === undefined ? { action } : { action, value });

export const sendState = (token: string, presenterToken: string, state: RemoteState) =>
    request<never>('POST', `/remote/${encodeURIComponent(token)}/state`, { presenter_token: presenterToken, state });

export const endPairing = (token: string, presenterToken: string) =>
    request<never>('DELETE', `/remote/${encodeURIComponent(token)}`, { presenter_token: presenterToken });
