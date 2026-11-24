export type AssetLayerName = 'back' | 'middle' | 'front';

export interface BoardAsset {
    layer: AssetLayerName;
    url: string;
}

export type BackdropAlign = 'top' | 'center' | 'bottom';

/** An image behind (or in front of) all boards, e.g. sky or mountains. */
export interface Backdrop {
    id: number;
    layer: 'back' | 'front';
    url: string;
    /** Parallax speed: 1 moves with the boards, lower is slower. */
    speed: number;
    align: BackdropAlign;
    /** Share of the stage height, in percent. */
    height: number;
    repeat: boolean;
}

export type CharacterPose = 'idle' | 'go' | 'pause' | 'greet';

export interface Character {
    id: number;
    name: string;
    /** Image URL per pose; poses without an image are missing. */
    images: Partial<Record<CharacterPose, string>>;
}

export interface Board {
    id: number;
    position: number;
    title: string | null;
    description: string | null;
    notes: string | null;
    day: number;
    time: string | null;
    location: string | null;
    is_night: boolean;
    mood: string | null;
    simulator_state: Record<string, unknown> | null;
    assets: BoardAsset[];
}

export interface Journey {
    title: string;
    slug: string;
    description: string | null;
    backdrops: Backdrop[];
    characters: Character[];
    boards: Board[];
}
