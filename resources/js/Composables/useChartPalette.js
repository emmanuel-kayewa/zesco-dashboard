/**
 * Centralised chart colour palette for the PP dashboard.
 * 
 * Design goals:
 *  - Softer, muted tones that are easy on the eyes
 *  - Consistent across all dashboard pages
 *  - Dark-mode friendly (mid-saturation works in both modes)
 *  - Accessible contrast for key semantic colours (RAG)
 */

// ── Categorical palette — 10 distinct, muted colours for breakdowns ──
export const CATEGORICAL = [
    '#6889c4', // soft blue
    '#5ba5b5', // muted teal
    '#7cae9a', // sage green
    '#d4a24e', // warm amber
    '#c47878', // dusty rose
    '#9b8ec4', // soft purple
    '#e09874', // peach
    '#6aaeae', // aqua
    '#b5a276', // khaki gold
    '#8fafd0', // sky blue
];

// ── Sequential palette — light→dark teal ramp for choropleths/heatmaps ──
export const SEQUENTIAL = [
    '#e0f0f0', // lightest
    '#a8d4d4',
    '#6ab5b5',
    '#3d9494',
    '#1a7070', // darkest
];

// ── RAG (Red-Amber-Green) semantic colours ──
export const RAG = {
    green: '#4ead7a',
    amber: '#d4a24e',
    red:   '#cf6060',
    grey:  '#a3adb8',
};

export const RAG_MAP = {
    Green:  RAG.green,
    Amber:  RAG.amber,
    Red:    RAG.red,
    Unknown: RAG.grey,
};

// ── Investment colours ──
export const INVESTMENT = {
    committed: '#6889c4',
    paid:      '#5ba5b5',
};

// ── Status colours (mapped by common project statuses) ──
export const STATUS = [
    '#6889c4', // Execution
    '#4ead7a', // Commissioned
    '#d4a24e', // Preparation
    '#a3adb8', // Other
];

// ── Sector card colours ──
export const SECTOR = {
    Generation:   '#4ead7a',
    Transmission: '#6889c4',
    Distribution: '#d4a24e',
    IPP:          '#9b8ec4',
};

// ── Risk category colours ──
export const RISK_CATEGORIES = [
    '#cf6060', // high priority red-ish
    '#d4a24e', // amber
    '#6889c4', // blue
    '#9b8ec4', // purple
    '#4ead7a', // green
    '#a3adb8', // grey
];

/**
 * Pick `n` categorical colours from the palette, cycling if needed.
 */
export function pickColors(n) {
    return Array.from({ length: n }, (_, i) => CATEGORICAL[i % CATEGORICAL.length]);
}

/**
 * Get colour for a RAG status string.
 */
export function ragColor(rag) {
    return RAG_MAP[(rag || '').charAt(0).toUpperCase() + (rag || '').slice(1).toLowerCase()] || RAG.grey;
}
