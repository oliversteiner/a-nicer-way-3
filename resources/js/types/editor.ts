export interface JourneySummary {
    id: number;
    title: string;
    slug: string;
    published: boolean;
    boards_count: number;
}

export interface EditorAsset {
    id: number;
    layer: 'back' | 'middle' | 'front';
    url: string;
}

export interface EditorBoard {
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
    simulator_screen: string | null;
    assets: EditorAsset[];
}

export interface EditorJourney {
    id: number;
    title: string;
    slug: string;
    description: string | null;
    published: boolean;
    boards: EditorBoard[];
}
