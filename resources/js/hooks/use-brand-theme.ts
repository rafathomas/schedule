import { useEffect } from 'react';
import type { Brand } from '@/lib/brand';
import { brandStyle } from '@/lib/brand';

const rememberedBrandKey = 'agendaflow.brand';

const readRememberedBrand = (): Brand | null => {
    if (typeof window === 'undefined') {
        return null;
    }

    try {
        const value = JSON.parse(
            window.localStorage.getItem(rememberedBrandKey) ?? 'null',
        ) as unknown;

        if (
            typeof value === 'object' &&
            value !== null &&
            'primary_color' in value &&
            typeof value.primary_color === 'string' &&
            /^#[0-9A-F]{6}$/i.test(value.primary_color)
        ) {
            return { primary_color: value.primary_color };
        }
    } catch {
        return null;
    }

    return null;
};

const rememberBrand = (brand: Brand): void => {
    if (
        typeof window === 'undefined' ||
        !/^#[0-9A-F]{6}$/i.test(brand.primary_color ?? '')
    ) {
        return;
    }

    try {
        window.localStorage.setItem(
            rememberedBrandKey,
            JSON.stringify({ primary_color: brand.primary_color }),
        );
    } catch {
        // Browsers may disable local storage; theming still works for the current session.
    }
};

export function useBrandTheme(
    brand?: Brand | null,
    fallbackToRemembered = false,
): Brand | null {
    const brandColor = brand?.primary_color;
    const resolvedBrand =
        brand ?? (fallbackToRemembered ? readRememberedBrand() : null);

    useEffect(() => {
        if (brandColor) {
            rememberBrand({ primary_color: brandColor });
        }
    }, [brandColor]);

    useEffect(() => {
        const root = document.documentElement;
        const styles = brandStyle(resolvedBrand?.primary_color);
        const previous = new Map<string, string>();
        const alreadyBranded = root.classList.contains('brand-theme');

        root.classList.add('brand-theme');

        Object.entries(styles).forEach(([property, value]) => {
            previous.set(property, root.style.getPropertyValue(property));
            root.style.setProperty(property, value);
        });

        return () => {
            previous.forEach((value, property) => {
                if (value) {
                    root.style.setProperty(property, value);
                } else {
                    root.style.removeProperty(property);
                }
            });

            if (!alreadyBranded) {
                root.classList.remove('brand-theme');
            }
        };
    }, [resolvedBrand?.primary_color]);

    return resolvedBrand;
}
