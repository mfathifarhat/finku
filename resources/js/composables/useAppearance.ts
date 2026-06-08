import type { ComputedRef, Ref } from 'vue';
import { computed, onMounted, ref } from 'vue';
import type { Appearance, ResolvedAppearance } from '@/types';

export type { Appearance, ResolvedAppearance };

export type UseAppearanceReturn = {
    appearance: Ref<Appearance>;
    resolvedAppearance: ComputedRef<ResolvedAppearance>;
    updateAppearance: (value: Appearance) => void;
};

export function updateTheme(value: Appearance): void {
    if (typeof window === 'undefined') {
        return;
    }
    // Always force absolute light theme by removing the dark class
    document.documentElement.classList.remove('dark');
}

const setCookie = (name: string, value: string, days = 365) => {
    if (typeof document === 'undefined') {
        return;
    }

    const maxAge = days * 24 * 60 * 60;

    document.cookie = `${name}=${value};path=/;max-age=${maxAge};SameSite=Lax`;
};

const mediaQuery = () => {
    if (typeof window === 'undefined') {
        return null;
    }

    return window.matchMedia('(prefers-color-scheme: dark)');
};

const getStoredAppearance = () => {
    return 'light';
};

const prefersDark = (): boolean => {
    return false;
};

const handleSystemThemeChange = () => {
    updateTheme('light');
};

export function initializeTheme(): void {
    if (typeof window === 'undefined') {
        return;
    }

    // Initialize theme to light
    updateTheme('light');
}

const appearance = ref<Appearance>('light');

export function useAppearance(): UseAppearanceReturn {
    onMounted(() => {
        appearance.value = 'light';
    });

    const resolvedAppearance = computed<ResolvedAppearance>(() => {
        return 'light';
    });

    function updateAppearance(value: Appearance) {
        appearance.value = 'light';

        // Store light in localStorage for client-side persistence
        localStorage.setItem('appearance', 'light');

        // Store light in cookie for SSR
        setCookie('appearance', 'light');

        updateTheme('light');
    }

    return {
        appearance,
        resolvedAppearance,
        updateAppearance,
    };
}
