import { defineStore } from 'pinia';
import { ref, watch } from 'vue';

export const useDirectorateStore = defineStore('directorate', () => {
    const activeDirectorate = ref(null);
    const sidebarFilters = ref({ from: '', to: '', category: '' });

    // Restore from sessionStorage on init
    try {
        const stored = sessionStorage.getItem('activeDirectorate');
        if (stored) activeDirectorate.value = JSON.parse(stored);
    } catch { /* ignore */ }

    // Persist to sessionStorage
    watch(activeDirectorate, (val) => {
        if (val) {
            sessionStorage.setItem('activeDirectorate', JSON.stringify(val));
        } else {
            sessionStorage.removeItem('activeDirectorate');
        }
    }, { deep: true });

    function enterDirectorate(directorate) {
        activeDirectorate.value = directorate;
        sidebarFilters.value = { from: '', to: '', category: '' };
    }

    function exitDirectorate() {
        activeDirectorate.value = null;
        sidebarFilters.value = { from: '', to: '', category: '' };
    }

    function updateFilters(filters) {
        sidebarFilters.value = { ...sidebarFilters.value, ...filters };
    }

    function updateSummary(summary) {
        if (activeDirectorate.value) {
            activeDirectorate.value = { ...activeDirectorate.value, summary };
        }
    }

    return {
        activeDirectorate,
        sidebarFilters,
        enterDirectorate,
        exitDirectorate,
        updateFilters,
        updateSummary,
    };
});
