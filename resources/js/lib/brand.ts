import type { CSSProperties } from 'react';

export const defaultBrandColor = '#1E3A8A';

export type Brand = {
    primary_color?: string | null;
    logo_url?: string | null;
};

type BrandStyle = CSSProperties & Record<`--${string}`, string>;

const normalizeColor = (color?: string | null) =>
    /^#[0-9A-F]{6}$/i.test(color ?? '')
        ? (color as string).toUpperCase()
        : defaultBrandColor;

const mixWithWhite = (color: string, whiteWeight: number) => {
    const channels = [1, 3, 5].map((start) =>
        Number.parseInt(color.slice(start, start + 2), 16),
    );

    return `#${channels
        .map((value) =>
            Math.round(value * (1 - whiteWeight) + 255 * whiteWeight)
                .toString(16)
                .padStart(2, '0'),
        )
        .join('')}`.toUpperCase();
};

const channel = (value: number) => {
    const normalized = value / 255;

    return normalized <= 0.04045
        ? normalized / 12.92
        : ((normalized + 0.055) / 1.055) ** 2.4;
};

export const brandForeground = (color?: string | null) => {
    const normalized = normalizeColor(color);
    const red = Number.parseInt(normalized.slice(1, 3), 16);
    const green = Number.parseInt(normalized.slice(3, 5), 16);
    const blue = Number.parseInt(normalized.slice(5, 7), 16);
    const luminance =
        0.2126 * channel(red) +
        0.7152 * channel(green) +
        0.0722 * channel(blue);

    return luminance > 0.179 ? '#111827' : '#FFFFFF';
};

export const brandStyle = (color?: string | null): BrandStyle => {
    const primary = normalizeColor(color);
    const foreground = brandForeground(primary);
    const darkPrimary = mixWithWhite(primary, 0.55);
    const darkForeground = brandForeground(darkPrimary);

    return {
        '--brand-primary': primary,
        '--brand-primary-foreground': foreground,
        '--brand-primary-dark': darkPrimary,
        '--brand-primary-dark-foreground': darkForeground,
        '--color-blue-50': `color-mix(in srgb, ${primary} 8%, white)`,
        '--color-blue-100': `color-mix(in srgb, ${primary} 15%, white)`,
        '--color-blue-200': `color-mix(in srgb, ${primary} 28%, white)`,
        '--color-blue-300': `color-mix(in srgb, ${primary} 42%, white)`,
        '--color-blue-400': `color-mix(in srgb, ${primary} 62%, white)`,
        '--color-blue-500': `color-mix(in srgb, ${primary} 78%, white)`,
        '--color-blue-600': `color-mix(in srgb, ${primary} 90%, white)`,
        '--color-blue-700': primary,
        '--color-blue-800': `color-mix(in srgb, ${primary} 85%, black)`,
        '--color-blue-900': `color-mix(in srgb, ${primary} 70%, black)`,
        '--color-blue-950': `color-mix(in srgb, ${primary} 55%, black)`,
    };
};
