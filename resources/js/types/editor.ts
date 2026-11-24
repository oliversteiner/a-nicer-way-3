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

export interface EditorCharacterImage {
    id: number;
    pose: 'idle' | 'go' | 'pause' | 'greet';
    url: string;
}

export interface EditorCharacter {
    id: number;
    name: string;
    images: EditorCharacterImage[];
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
    characters: EditorCharacter[];
    boards: EditorBoard[];
}
