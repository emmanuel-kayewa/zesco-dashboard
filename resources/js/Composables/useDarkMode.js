import { ref, watch } from 'vue';

// Singleton state — shared across all consumers so every component
// reacts when any component toggles dark mode.
const isDark = ref(false);
let initialized = false;

export function useDarkMode() {
    if (!initialized) {
        initialized = true;

        // Initialize from localStorage or system preference
        const stored = localStorage.getItem('theme');
        if (stored) {
            isDark.value = stored === 'dark';
        } else {
            isDark.value = window.matchMedia('(prefers-color-scheme: dark)').matches;
        }

        // Apply class to html element
        const apply = () => {
            if (isDark.value) {
                document.documentElement.classList.add('dark');
            } else {
                document.documentElement.classList.remove('dark');
            }
        };

        apply();

        watch(isDark, (val) => {
            localStorage.setItem('theme', val ? 'dark' : 'light');
            apply();
        });
    }

    const toggle = () => {
        isDark.value = !isDark.value;
    };

    return { isDark, toggle };
}
