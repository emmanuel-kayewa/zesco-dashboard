import { ref, watch, effectScope } from "vue";

// Singleton state — shared across all consumers so every component
// reacts when any component toggles dark mode.
const isDark = ref(false);
let initialized = false;

// Module-level effect scope so the watcher is never tied to (or
// cleaned up by) any single component's lifecycle.
const scope = effectScope(true);

function init() {
  initialized = true;

  // Initialize from localStorage or system preference
  const stored = localStorage.getItem("theme");
  if (stored) {
    isDark.value = stored === "dark";
  } else {
    isDark.value = window.matchMedia("(prefers-color-scheme: dark)").matches;
  }

  // Apply class to html element
  const apply = () => {
    if (isDark.value) {
      document.documentElement.classList.add("dark");
    } else {
      document.documentElement.classList.remove("dark");
    }
  };

  apply();

  scope.run(() => {
    watch(isDark, (val) => {
      localStorage.setItem("theme", val ? "dark" : "light");
      apply();
    });
  });
}

export function useDarkMode() {
  if (!initialized) {
    init();
  }

  const toggle = () => {
    isDark.value = !isDark.value;
  };

  return { isDark, toggle };
}
