export type RemoteAction = 'next' | 'previous' | 'goto' | 'timer-toggle' | 'timer-reset' | 'hello';

export interface RemoteCommand {
    action: RemoteAction;
    /** Board position (1-based) for `goto`. */
    value: number | null;
}

export interface RemoteTimerState {
    remaining: number;
    total: number;
    running: boolean;
    nearEnd: boolean;
    timeOut: boolean;
}

export interface RemoteBoardState {
    position: number;
    title: string | null;
    notes: string | null;
    day: number | null;
    time: string | null;
    location: string | null;
}

/** What the presentation tells the remote control. */
export interface RemoteState {
    index: number;
    count: number;
    board: RemoteBoardState | null;
    timer: RemoteTimerState;
}

export interface Pairing {
    remote_token: string;
    presenter_token: string;
    remote_url: string;
}
