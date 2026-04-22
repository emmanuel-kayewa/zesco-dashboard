<template>
    <div class="flex flex-col h-full">
        <!-- Logo -->
        <div class="flex items-center h-16 px-4 border-b border-gray-200 dark:border-gray-700 flex-shrink-0">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 flex items-center justify-center flex-shrink-0">
                    <img src="/images/zesco_black_logo.svg" alt="ZESCO" class="w-20 h-20 object-contain dark:hidden" />
                    <img src="/images/zesco_white_logo.svg" alt="ZESCO" class="w-20 h-20 object-contain hidden dark:block" />
                </div>
                <transition name="fade">
                    <div v-if="expanded" class="overflow-hidden">
                        <h1 class="text-sm font-bold text-gray-900 dark:text-white leading-tight">ZESCO</h1>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Executive Dashboard</p>
                    </div>
                </transition>
            </div>
        </div>

        <!-- Back Button -->
        <div class="px-3 pt-3 pb-1 flex-shrink-0">
            <button
                @click="handleBack"
                :class="[
                    'w-full flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-medium transition-colors duration-150',
                    'text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-gray-700/50'
                ]"
                :title="expanded ? '' : 'Back to Main Dashboard'"
            >
                <svg class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                <span v-if="expanded" class="truncate">Main Dashboard</span>
            </button>
        </div>

        <!-- Directorate Header (collapsed = just icon) -->
        <div v-if="!expanded" class="px-3 py-2 flex-shrink-0">
            <div
                class="w-full flex items-center justify-center"
                :title="directorate.name"
            >
                <div
                    class="w-10 h-10 rounded-xl flex items-center justify-center text-white font-bold text-sm"
                    :style="{ backgroundColor: directorate.color || '#374151' }"
                >
                    {{ directorate.code?.substring(0, 2) }}
                </div>
            </div>
        </div>

        <!-- Navigation Links -->
        <nav class="flex-shrink-0 px-3 py-2 space-y-0.5">
            <template v-if="isPP">
                <SidebarLink href="/pp/dashboard" icon="dashboard" :label="expanded ? 'Overview' : ''" :active="currentUrl === '/pp/dashboard'" />
                <SidebarLink href="/pp/dashboard/explore" icon="chart" :label="expanded ? 'Portfolio Breakdown' : ''" :active="currentUrl.startsWith('/pp/dashboard/explore')" />
                <!-- <SidebarLink href="/pp/dashboard/grid-studies" icon="map" :label="expanded ? 'Grid Studies' : ''" :active="currentUrl.startsWith('/pp/dashboard/grid-studies')" /> -->
                <SidebarLink href="/pp/weekly-reports" icon="document" :label="expanded ? 'Weekly Reports' : ''" :active="currentUrl.startsWith('/pp/weekly-reports')" />
                <SidebarLink href="/pp/projects" icon="folder" :label="expanded ? 'Data Management' : ''" :active="currentUrl.startsWith('/pp/projects') || currentUrl.startsWith('/pp/milestones') || currentUrl.startsWith('/pp/financials') || currentUrl.startsWith('/pp/risks')" />
            </template>
            <template v-else>
                <SidebarLink
                    :href="`/dashboard/directorate/${directorate.slug}`"
                    icon="dashboard"
                    :label="expanded ? 'Overview' : ''"
                    :active="currentUrl.includes(directorate.slug)"
                />
            </template>
        </nav>

        <!-- Divider -->
        <div class="mx-4 border-t border-gray-200 dark:border-gray-700 my-1 flex-shrink-0"></div>

        <!-- Directorate Summary Card -->
        <div v-if="expanded" class="px-3 py-2 flex-shrink-0">
            <div class="rounded-xl bg-gray-100 dark:bg-gray-700/30 border border-gray-100 dark:border-gray-600/30 p-3">
                <div class="flex items-center gap-3 mb-3">
                    <div
                        class="w-9 h-9 rounded-lg flex items-center justify-center text-white font-bold text-xs flex-shrink-0"
                        :style="{ backgroundColor: directorate.color || '#374151' }"
                    >
                        {{ directorate.code?.substring(0, 2) }}
                    </div>
                    <div class="min-w-0">
                        <h3 class="text-sm font-semibold text-gray-900 dark:text-white truncate">{{ directorate.name }}</h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400 truncate">{{ directorate.head_name || 'Head not assigned' }}</p>
                    </div>
                </div>

                <!-- Summary Stats -->
                <div class="grid grid-cols-3 gap-2 text-center">
                    <template v-if="isPP && summary">
                        <div class="bg-white dark:bg-gray-800 rounded-lg p-2 min-w-0">
                            <p
                                class="text-[13px] font-bold text-gray-900 dark:text-white truncate tabular-nums w-full"
                                :title="String(summary.totalProjects ?? 0)"
                            >
                                {{ summary.totalProjects || 0 }}
                            </p>
                            <p class="text-[10px] text-gray-500 leading-tight">Projects</p>
                        </div>
                        <div class="bg-white dark:bg-gray-800 rounded-lg p-2 min-w-0">
                            <p
                                class="text-[13px] font-bold text-zesco-600 dark:text-zesco-400 truncate tabular-nums w-full"
                                :title="`$${fmtM(summary.totalCommitted)}`"
                            >
                                ${{ fmtM(summary.totalCommitted) }}
                            </p>
                            <p class="text-[10px] text-gray-500 leading-tight">Committed</p>
                        </div>
                        <div class="bg-white dark:bg-gray-800 rounded-lg p-2 min-w-0">
                            <p
                                class="text-[13px] font-bold truncate tabular-nums w-full"
                                :class="(summary.spendPct ?? 0) >= 50 ? 'text-green-600' : 'text-amber-500'"
                                :title="String((summary.spendPct ?? 0) + '%')"
                            >
                                {{ summary.spendPct ?? 0 }}%
                            </p>
                            <p class="text-[10px] text-gray-500 leading-tight">Spent</p>
                        </div>
                    </template>
                    <template v-else-if="summary">
                        <div class="bg-white dark:bg-gray-800 rounded-lg p-2 min-w-0">
                            <p
                                class="text-[13px] font-bold text-gray-900 dark:text-white truncate tabular-nums w-full"
                                :title="String(summary.total ?? 0)"
                            >
                                {{ summary.total || 0 }}
                            </p>
                            <p class="text-[10px] text-gray-500 leading-tight">KPIs</p>
                        </div>
                        <div class="bg-white dark:bg-gray-800 rounded-lg p-2 min-w-0">
                            <p
                                class="text-[13px] font-bold truncate tabular-nums w-full"
                                :class="(summary.completion_percentage ?? 0) >= 75 ? 'text-green-600' : 'text-amber-500'"
                                :title="String((summary.completion_percentage || 0) + '%')"
                            >
                                {{ summary.completion_percentage || 0 }}%
                            </p>
                            <p class="text-[10px] text-gray-500 leading-tight">Complete</p>
                        </div>
                        <div class="bg-white dark:bg-gray-800 rounded-lg p-2 min-w-0">
                            <p
                                class="text-[13px] font-bold truncate tabular-nums w-full"
                                :class="(summary.high_risk ?? 0) > 2 ? 'text-red-500' : 'text-gray-900 dark:text-white'"
                                :title="String(summary.high_risk || 0)"
                            >
                                {{ summary.high_risk || 0 }}
                            </p>
                            <p class="text-[10px] text-gray-500 leading-tight">High Risk</p>
                        </div>
                    </template>
                </div>
            </div>
        </div>

        <!-- Divider -->
        <!-- <div v-if="expanded && showFilters" class="mx-4 border-t border-gray-200 dark:border-gray-700 my-1 flex-shrink-0"></div> -->

        <!-- Filters Section (scrollable) -->
        <!-- <div v-if="expanded && showFilters" class="flex-1 overflow-y-auto px-3 py-2">
            <p class="text-xs font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500 mb-2 px-1">Filters</p>

            <div class="space-y-3">
                <div>
                    <label class="text-xs font-medium text-gray-600 dark:text-gray-400 mb-1 block px-1">Date Range</label>
                    <DateRangePicker
                        v-model:from="localFilters.from"
                        v-model:to="localFilters.to"
                        @apply="emitFilterChange"
                        @clear="clearFilters"
                        compact
                    />
                </div>

                <div v-if="kpiCategories.length > 0">
                    <label class="text-xs font-medium text-gray-600 dark:text-gray-400 mb-1 block px-1">Category</label>
                    <Select
                        v-model="localFilters.category"
                        :options="[{ value: '', label: 'All Categories' }, ...kpiCategories.map(c => ({ value: c, label: c }))]"
                        size="sm"
                        class="w-full"
                        @update:modelValue="emitFilterChange"
                    />
                </div>
            </div>

            //Export Actions
            <div v-if="directorate.slug" class="mt-4 space-y-1.5">
                <p class="text-xs font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500 mb-2 px-1">Export</p>
                <div class="flex items-center gap-2">
                    <a :href="`/export/directorate/${directorate.slug}/pdf`" class="flex-1 btn-secondary text-xs text-center py-1.5">PDF</a>
                    <a :href="`/export/directorate/${directorate.slug}/excel`" class="flex-1 btn-secondary text-xs text-center py-1.5">Excel</a>
                </div>
            </div>
        </div> -->

        <!-- PP Explorer Filters (sidebar version, large screens only) -->
        <div v-if="expanded && isPP && explorerState.active" class="flex-1 overflow-y-auto px-3 py-2">
            <div class="mx-1 border-t border-gray-200 dark:border-gray-700 mb-3"></div>

            <!-- View Mode Toggle -->
            <p class="text-xs font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500 mb-2 px-1">View</p>
            <div class="flex items-center gap-1 bg-gray-100 dark:bg-gray-700/50 rounded-lg p-1 mb-3">
                <button
                    @click="directorateStore.setExplorerViewMode('classic')"
                    :class="[
                        'flex-1 px-3 py-1.5 text-xs font-medium rounded-md transition-all duration-200 flex items-center justify-center gap-1.5',
                        directorateStore.explorerViewMode === 'classic'
                            ? 'bg-white dark:bg-gray-800 text-gray-900 dark:text-white shadow-sm'
                            : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white'
                    ]"
                >
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16" /></svg>
                    <span>Classic</span>
                </button>
                <button
                    @click="directorateStore.setExplorerViewMode('compact')"
                    :class="[
                        'flex-1 px-3 py-1.5 text-xs font-medium rounded-md transition-all duration-200 flex items-center justify-center gap-1.5',
                        directorateStore.explorerViewMode === 'compact'
                            ? 'bg-white dark:bg-gray-800 text-gray-900 dark:text-white shadow-sm'
                            : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white'
                    ]"
                >
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" /></svg>
                    <span>Compact</span>
                </button>
            </div>

            <p class="text-xs font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500 mb-2 px-1">Filters</p>

            <!-- Active filter chips -->
            <div class="space-y-1.5 mb-3">
                <template v-if="hasExplorerFilters">
                    <div v-for="(val, dim) in explorerState.appliedFilters" :key="dim"
                         class="flex items-center justify-between px-2 py-1.5 rounded-lg border"
                         :style="{ backgroundColor: 'var(--palette-accent-lighter)', borderColor: 'var(--palette-accent-light)' }">
                        <div class="min-w-0">
                            <p class="text-[10px] uppercase leading-none mb-0.5 opacity-60" :style="{ color: 'var(--palette-accent-dark)' }">{{ explorerState.dimensionLabels[dim] || dim }}</p>
                            <p class="text-xs font-medium truncate" :style="{ color: 'var(--palette-accent-dark)' }">{{ val }}</p>
                        </div>
                        <button @click="removeExplorerFilter(dim)" class="p-0.5 text-gray-400 hover:text-red-500 transition-colors flex-shrink-0" title="Remove">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                        </button>
                    </div>
                    <button @click="clearExplorerFilters" class="text-xs text-red-500 hover:text-red-700 font-medium px-1">
                        Clear All
                    </button>
                </template>
                <p v-else class="text-xs text-gray-400 italic px-1">No filters applied</p>
            </div>

            <!-- Add filter -->
            <div class="space-y-2">
                <Select
                    v-model="sidebarAddFilterDim"
                    :options="sidebarAvailableDimensions"
                    placeholder="+ Add Filter"
                    size="sm"
                    class="w-full"
                />
                <Select
                    v-if="sidebarAddFilterDim"
                    v-model="sidebarAddFilterVal"
                    :options="sidebarAddFilterOptions"
                    placeholder="Select value…"
                    size="sm"
                    class="w-full"
                />
            </div>
        </div>

        <!-- Fill remaining space when no filters -->
        <div v-if="!expanded || (!showFilters && !(isPP && explorerState.active))" class="flex-1"></div>
    </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import { useDirectorateStore } from '@/stores/useDirectorateStore';
import SidebarLink from './SidebarLink.vue';
// import DateRangePicker from '@/Components/UI/DateRangePicker.vue';
import Select from '@/Components/UI/Select.vue';

const props = defineProps({
    directorate: { type: Object, required: true },
    expanded: { type: Boolean, default: true },
    summary: { type: Object, default: null },
    kpiCategories: { type: Array, default: () => [] },
    showFilters: { type: Boolean, default: true },
});

// const emit = defineEmits(['filter-change', 'exit']);

const page = usePage();
const directorateStore = useDirectorateStore();
const currentUrl = computed(() => page.url);
const isPP = computed(() => props.directorate?.code === 'PP');

// const localFilters = ref({
//     from: directorateStore.sidebarFilters.from || '',
//     to: directorateStore.sidebarFilters.to || '',
//     category: directorateStore.sidebarFilters.category || '',
// });

// PP Explorer filter state from store
const explorerState = computed(() => directorateStore.explorerFilterState);
const hasExplorerFilters = computed(() => Object.keys(explorerState.value.appliedFilters || {}).length > 0);

const sidebarAddFilterDim = ref('');
const sidebarAddFilterVal = ref('');

const sidebarAvailableDimensions = computed(() => {
    const applied = explorerState.value.appliedFilters || {};
    const labels = explorerState.value.dimensionLabels || {};
    const options = explorerState.value.filterOptions || {};
    return Object.entries(labels)
        .filter(([dim]) => !applied[dim] && (options[dim] || []).length > 0)
        .map(([value, label]) => ({ value, label }));
});

const sidebarAddFilterOptions = computed(() => {
    if (!sidebarAddFilterDim.value) return [];
    return (explorerState.value.filterOptions?.[sidebarAddFilterDim.value] || []).map(opt => ({
        value: opt,
        label: opt,
    }));
});

watch(sidebarAddFilterDim, () => { sidebarAddFilterVal.value = ''; });

watch(sidebarAddFilterVal, (newVal) => {
    if (newVal && sidebarAddFilterDim.value) {
        const current = { ...(explorerState.value.appliedFilters || {}) };
        current[sidebarAddFilterDim.value] = newVal;
        sidebarAddFilterDim.value = '';
        sidebarAddFilterVal.value = '';
        router.get('/pp/dashboard/explore', current);
    }
});

function removeExplorerFilter(dim) {
    const current = { ...(explorerState.value.appliedFilters || {}) };
    delete current[dim];
    if (Object.keys(current).length === 0) {
        router.get('/pp/dashboard/explore');
    } else {
        router.get('/pp/dashboard/explore', current);
    }
}

function clearExplorerFilters() {
    router.get('/pp/dashboard/explore');
}

function handleBack() {
    directorateStore.exitDirectorate();
    router.visit('/dashboard');
}

// function emitFilterChange() {
//     directorateStore.updateFilters(localFilters.value);
//     emit('filter-change', { ...localFilters.value });
// }

// function clearFilters() {
//     localFilters.value = { from: '', to: '', category: '' };
//     emitFilterChange();
// }

function fmtM(val) {
    const n = Number(val) || 0;
    if (n >= 1e9) return (n / 1e9).toFixed(2) + 'B';
    if (n >= 1e6) return (n / 1e6).toFixed(1) + 'M';
    if (n >= 1e3) return (n / 1e3).toFixed(1) + 'K';
    return n.toLocaleString();
}
</script>
