export type AssetLayerName = 'back' | 'middle' | 'front';

export interface BoardAsset {
    layer: AssetLayerName;
    path: string;
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
    boards: Board[];
}
