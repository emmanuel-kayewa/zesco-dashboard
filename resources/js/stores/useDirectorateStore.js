import { defineStore } from 'pinia';
import { ref, watch } from 'vue';

export const useDirectorateStore = defineStore('directorate', () => {
    const activeDirectorate = ref(null);
    const sidebarFilters = ref({ from: '', to: '', category: '' });

    // PP Explorer filter state (shared between Explorer page and DirectorateSidebar)
    const explorerFilterState = ref({
        appliedFilters: {},
        filterOptions: {},
        dimensionLabels: {},
        active: false,
    });

    // PP Explorer view mode (shared between Explorer page and DirectorateSidebar)
    const explorerViewMode = ref('classic');

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

    function setExplorerViewMode(mode) {
        explorerViewMode.value = mode;
    }

    function setExplorerFilters(data) {
        explorerFilterState.value = {
            appliedFilters: data.appliedFilters || {},
            filterOptions: data.filterOptions || {},
            dimensionLabels: data.dimensionLabels || {},
            active: true,
        };
    }

    function clearExplorerFilters() {
        explorerFilterState.value = {
            appliedFilters: {},
            filterOptions: {},
            dimensionLabels: {},
            active: false,
        };
    }

    return {
        activeDirectorate,
        sidebarFilters,
        explorerFilterState,
        explorerViewMode,
        enterDirectorate,
        exitDirectorate,
        updateFilters,
        updateSummary,
        setExplorerViewMode,
        setExplorerFilters,
        clearExplorerFilters,
    };
});
