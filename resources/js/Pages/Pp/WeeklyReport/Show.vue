<template>
    <AppLayout>
        <template #title>Weekly Report — Week {{ report.week_number }}, {{ report.year }}</template>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 py-6 space-y-6">
                <!-- Header -->
                <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
                    <div class="flex items-start gap-3">
                        <Link :href="backUrl" class="p-2 rounded-lg text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition flex-shrink-0 mt-0.5">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                            </svg>
                        </Link>
                        <div class="min-w-0">
                            <h1 class="text-xl sm:text-2xl font-bold text-gray-900 dark:text-white">
                                ZESCO, IPP Solar PV and Transmission Projects
                            </h1>
                            <p class="text-base sm:text-lg font-semibold text-gray-600 dark:text-gray-400 mt-0.5">
                                Weekly Brief Report — Week {{ report.week_number }}
                            </p>
                            <div class="flex flex-wrap items-center gap-x-4 gap-y-1 mt-2 text-sm text-gray-500 dark:text-gray-400">
                                <span class="flex items-center gap-1.5">
                                    <svg class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                                    {{ formatDate(report.report_date) }}
                                </span>
                                <span class="flex items-center gap-1.5">
                                    <svg class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                                    Created by: <span class="font-medium text-gray-700 dark:text-gray-300">{{ report.author?.name || 'Unknown' }}</span>
                                </span>
                            </div>
                        </div>
                    </div>

                    <div v-if="canEdit" class="flex items-center gap-2">
                        <Link :href="`/pp/weekly-reports/${report.id}/edit`" class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg text-sm font-medium text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 border border-gray-200 dark:border-gray-600 transition">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                            Edit
                        </Link>
                    </div>
                </div>

                <!-- Tabs + Filter -->
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                    <PillTabs
                        v-model="activeTab"
                        :tabs="[
                            { value: 'tabular', label: 'Tabular' },
                            { value: 'visual', label: 'Visual' },
                        ]"
                        aria-label="View tabs"
                    />

                    <!-- Drill-down filter chip (Visual tab) -->
                    <div v-if="activeTab === 'visual' && activeDrillFilter" class="flex items-center gap-2">
                        <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-medium bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300">
                            <span class="text-[10px] uppercase text-gray-500 dark:text-gray-400">{{ activeDrillFilter.dimension }}:</span>
                            {{ activeDrillFilter.value }}
                            <button @click="clearDrill" class="ml-1 text-gray-400 hover:text-gray-700 dark:hover:text-white">
                                <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                            </button>
                        </span>
                    </div>
                </div>

                <!-- ═══ TABULAR VIEW ═══ -->
                <template v-if="activeTab === 'tabular'">
                    <div v-for="section in report.sections" :key="section.id" class="space-y-0">
                        <Card>
                            <template #title>
                                <div class="flex items-center gap-3">
                                    <span class="flex items-center justify-center w-8 h-8 rounded-lg bg-gray-100 dark:bg-gray-700 text-gray-900 dark:text-white text-sm font-bold flex-shrink-0">
                                        {{ section.section_number }}
                                    </span>
                                    <span>{{ section.title }}</span>
                                </div>
                            </template>

                            <!-- Project Entries Table -->
                            <template v-if="section.section_type !== 'net_metering'">
                                <div class="overflow-x-auto">
                                    <table class="w-full text-sm">
                                        <thead>
                                            <tr class="border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/30">
                                                <th class="text-left py-2.5 px-3 text-xs font-semibold text-gray-500 uppercase">Name of Project</th>
                                                <th class="text-left py-2.5 px-3 text-xs font-semibold text-gray-500 uppercase">Location</th>
                                                <th class="text-left py-2.5 px-3 text-xs font-semibold text-gray-500 uppercase">Developer</th>
                                                <th v-if="section.section_type !== 'transmission_projects'" class="text-right py-2.5 px-3 text-xs font-semibold text-gray-500 uppercase">Size (MW)</th>
                                                <th class="text-left py-2.5 px-3 text-xs font-semibold text-gray-500 uppercase">Project Type</th>
                                                <th v-if="section.section_type !== 'completed_solar'" class="text-left py-2.5 px-3 text-xs font-semibold text-gray-500 uppercase">Est. Completion</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr v-for="entry in section.project_entries" :key="entry.id" class="border-b border-gray-100 dark:border-gray-700/50 hover:bg-gray-50 dark:hover:bg-gray-700/20">
                                                <td class="py-2 px-3 font-medium text-gray-900 dark:text-white">
                                                    {{ entry.name }}
                                                    <p v-if="entry.notes" class="text-xs text-gray-400 mt-0.5 font-normal">{{ entry.notes }}</p>
                                                </td>
                                                <td class="py-2 px-3 text-gray-600 dark:text-gray-300">{{ entry.location || '—' }}</td>
                                                <td class="py-2 px-3 text-gray-600 dark:text-gray-300">{{ entry.developer || '—' }}</td>
                                                <td v-if="section.section_type !== 'transmission_projects'" class="py-2 px-3 text-right font-semibold text-gray-900 dark:text-white">
                                                    {{ entry.size_mw ? Number(entry.size_mw).toLocaleString('en-US', { minimumFractionDigits: 1 }) : '—' }}
                                                </td>
                                                <td class="py-2 px-3 text-gray-600 dark:text-gray-300">{{ entry.project_type || '—' }}</td>
                                                <td v-if="section.section_type !== 'completed_solar'" class="py-2 px-3 text-gray-600 dark:text-gray-300">{{ entry.est_completion || '—' }}</td>
                                            </tr>
                                        </tbody>
                                        <tfoot v-if="section.section_type !== 'transmission_projects'">
                                            <tr class="border-t-2 border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-700/30">
                                                <td class="py-2.5 px-3 font-bold text-gray-900 dark:text-white">
                                                    TOTAL PROJECTS: {{ section.project_entries?.length || 0 }}
                                                </td>
                                                <td colspan="2"></td>
                                                <td class="py-2.5 px-3 text-right font-bold text-gray-900 dark:text-white">
                                                    {{ sectionTotalMw(section) }}
                                                </td>
                                                <td :colspan="section.section_type === 'completed_solar' ? 1 : 2"></td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </template>

                            <!-- Net Metering Table -->
                            <template v-else>
                                <div class="overflow-x-auto">
                                    <table class="w-full text-sm">
                                        <thead>
                                            <tr class="border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/30">
                                                <th class="text-left py-2.5 px-3 text-xs font-semibold text-gray-500 uppercase">Key Initiative</th>
                                                <th class="text-left py-2.5 px-3 text-xs font-semibold text-gray-500 uppercase">Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr v-for="entry in section.net_metering_entries" :key="entry.id" class="border-b border-gray-100 dark:border-gray-700/50 hover:bg-gray-50 dark:hover:bg-gray-700/20">
                                                <td class="py-2 px-3 font-medium text-gray-900 dark:text-white">{{ entry.key_initiative }}</td>
                                                <td class="py-2 px-3 font-semibold text-gray-900 dark:text-white">{{ entry.status_value }}</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </template>
                        </Card>
                    </div>
                </template>

                <!-- ═══ VISUAL VIEW ═══ -->
                <template v-if="activeTab === 'visual'">
                    <!-- Summary Cards -->
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
                            <p class="text-xs text-gray-500 dark:text-gray-400 uppercase font-semibold">Completed Projects</p>
                            <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">{{ completedCount }}</p>
                            <p class="text-sm text-gray-400">{{ completedMw }} MW</p>
                        </div>
                        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
                            <p class="text-xs text-gray-500 dark:text-gray-400 uppercase font-semibold">Under Construction</p>
                            <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">{{ constructionCount }}</p>
                            <p class="text-sm text-gray-400">{{ constructionMw }} MW</p>
                        </div>
                        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
                            <p class="text-xs text-gray-500 dark:text-gray-400 uppercase font-semibold">Transmission Projects</p>
                            <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">{{ transmissionCount }}</p>
                        </div>
                        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
                            <p class="text-xs text-gray-500 dark:text-gray-400 uppercase font-semibold">Net Metering</p>
                            <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">{{ netMeteringProsumers }}</p>
                            <p class="text-sm text-gray-400">Prosumers</p>
                        </div>
                    </div>

                    <!-- Charts Row 1: Capacity by Project Type -->
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <ChartCard title="Capacity by Project Type (MW)" :baseHeight="320">
                            <template #default="{ zoomedHeight }">
                                <PieChart
                                    :data="capacityByTypeData"
                                    :height="zoomedHeight"
                                    @chart-click="(p) => handleDrill('project_type', p)"
                                />
                            </template>
                        </ChartCard>
                        <ChartCard title="Projects by Developer (MW)" :baseHeight="320">
                            <template #default="{ zoomedHeight }">
                                <BarChart
                                    :data="capacityByDeveloperData"
                                    :height="zoomedHeight"
                                    seriesName="Capacity (MW)"
                                    @chart-click="(p) => handleDrill('developer', p)"
                                />
                            </template>
                        </ChartCard>
                    </div>

                    <!-- Charts Row 2: Construction timeline & sector breakdown -->
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <ChartCard title="Under Construction — Capacity by Project" :baseHeight="320">
                            <template #default="{ zoomedHeight }">
                                <BarChart
                                    :data="constructionByProjectData"
                                    :height="zoomedHeight"
                                    seriesName="MW"
                                    :horizontal="true"
                                />
                            </template>
                        </ChartCard>
                        <ChartCard title="Capacity by Status" :baseHeight="320">
                            <template #default="{ zoomedHeight }">
                                <PieChart
                                    :data="capacityByStatusData"
                                    :height="zoomedHeight"
                                    @chart-click="(p) => handleDrill('status', p)"
                                />
                            </template>
                        </ChartCard>
                    </div>

                    <!-- Net Metering Summary Cards -->
                    <Card title="Net Metering Summary">
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                            <div v-for="entry in netMeteringSection?.net_metering_entries || []" :key="entry.id"
                                class="bg-gray-50 dark:bg-gray-700/30 rounded-xl p-4 border border-gray-100 dark:border-gray-600/30">
                                <p class="text-xs text-gray-500 dark:text-gray-400 font-medium mb-1">{{ entry.key_initiative }}</p>
                                <p class="text-lg font-bold text-gray-900 dark:text-white">{{ entry.status_value }}</p>
                            </div>
                        </div>
                    </Card>

                    <!-- Drill-down detail table -->
                    <Card v-if="activeDrillFilter" :title="`Filtered: ${activeDrillFilter.dimension} = ${activeDrillFilter.value}`">
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/30">
                                        <th class="text-left py-2.5 px-3 text-xs font-semibold text-gray-500 uppercase">Project</th>
                                        <th class="text-left py-2.5 px-3 text-xs font-semibold text-gray-500 uppercase">Location</th>
                                        <th class="text-left py-2.5 px-3 text-xs font-semibold text-gray-500 uppercase">Developer</th>
                                        <th class="text-right py-2.5 px-3 text-xs font-semibold text-gray-500 uppercase">MW</th>
                                        <th class="text-left py-2.5 px-3 text-xs font-semibold text-gray-500 uppercase">Type</th>
                                        <th class="text-left py-2.5 px-3 text-xs font-semibold text-gray-500 uppercase">Section</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="entry in filteredEntries" :key="entry.id" class="border-b border-gray-100 dark:border-gray-700/50 hover:bg-gray-50 dark:hover:bg-gray-700/20">
                                        <td class="py-2 px-3 font-medium text-gray-900 dark:text-white">{{ entry.name }}</td>
                                        <td class="py-2 px-3 text-gray-600 dark:text-gray-300">{{ entry.location || '—' }}</td>
                                        <td class="py-2 px-3 text-gray-600 dark:text-gray-300">{{ entry.developer || '—' }}</td>
                                        <td class="py-2 px-3 text-right font-semibold">{{ entry.size_mw || '—' }}</td>
                                        <td class="py-2 px-3 text-gray-600 dark:text-gray-300">{{ entry.project_type || '—' }}</td>
                                        <td class="py-2 px-3 text-gray-600 dark:text-gray-300">{{ entry._sectionTitle }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </Card>
                </template>

                <!-- Notes -->
                <Card v-if="report.notes" title="Notes & Comments">
                    <p class="text-sm text-gray-700 dark:text-gray-300 whitespace-pre-wrap">{{ report.notes }}</p>
                </Card>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Components/Layout/AppLayout.vue';
import Card from '@/Components/UI/Card.vue';
import ChartCard from '@/Components/UI/ChartCard.vue';
import PillTabs from '@/Components/UI/PillTabs.vue';
import PieChart from '@/Components/Charts/PieChart.vue';
import BarChart from '@/Components/Charts/BarChart.vue';

const props = defineProps({
    report: { type: Object, required: true },
    readOnly: { type: Boolean, default: false },
});

const page = usePage();
const auth = computed(() => page.props.auth?.user);

const canEdit = computed(() => !props.readOnly && !!auth.value);

const backUrl = computed(() => {
    if (!props.readOnly) return '/pp/weekly-reports';
    return '/weekly-reports';
});

const activeTab = ref('tabular');
const activeDrillFilter = ref(null);

// ── Section helpers ──────────────────────────────────────

const sections = computed(() => props.report.sections || []);

const completedSection = computed(() => sections.value.find(s => s.section_type === 'completed_solar'));
const constructionSection = computed(() => sections.value.find(s => s.section_type === 'construction_projects'));
const transmissionSection = computed(() => sections.value.find(s => s.section_type === 'transmission_projects'));
const netMeteringSection = computed(() => sections.value.find(s => s.section_type === 'net_metering'));

// ── All project entries (flattened, for charts & drill-down) ──

const allProjectEntries = computed(() => {
    const entries = [];
    for (const s of sections.value) {
        if (s.section_type === 'net_metering') continue;
        for (const e of (s.project_entries || [])) {
            entries.push({
                ...e,
                _sectionType: s.section_type,
                _sectionTitle: s.title,
            });
        }
    }
    return entries;
});

// ── Summary stats ─────────────────────────────────────────

const completedCount = computed(() => completedSection.value?.project_entries?.length || 0);
const completedMw = computed(() => sectionTotalMw(completedSection.value));
const constructionCount = computed(() => constructionSection.value?.project_entries?.length || 0);
const constructionMw = computed(() => sectionTotalMw(constructionSection.value));
const transmissionCount = computed(() => transmissionSection.value?.project_entries?.length || 0);

const netMeteringProsumers = computed(() => {
    const entry = (netMeteringSection.value?.net_metering_entries || [])
        .find(e => e.key_initiative?.toLowerCase().includes('prosumer'));
    return entry?.status_value || '—';
});

function sectionTotalMw(section) {
    if (!section?.project_entries) return '0';
    const total = section.project_entries.reduce((sum, e) => sum + (Number(e.size_mw) || 0), 0);
    return total.toLocaleString('en-US', { minimumFractionDigits: 1, maximumFractionDigits: 1 });
}

// ── Chart data ────────────────────────────────────────────

function groupBy(entries, key) {
    const groups = {};
    for (const e of entries) {
        const label = e[key] || 'Other';
        if (!groups[label]) groups[label] = 0;
        groups[label] += Number(e.size_mw) || 0;
    }
    return groups;
}

const capacityByTypeData = computed(() => {
    const source = activeDrillFilter.value ? filteredEntries.value : allProjectEntries.value;
    const groups = groupBy(source, 'project_type');
    return Object.entries(groups).map(([name, value]) => ({ name, value: Math.round(value * 10) / 10 }));
});

const capacityByDeveloperData = computed(() => {
    const source = activeDrillFilter.value ? filteredEntries.value : allProjectEntries.value;
    const groups = groupBy(source, 'developer');
    return Object.entries(groups)
        .sort((a, b) => b[1] - a[1])
        .slice(0, 12)
        .map(([label, value]) => ({ label, value: Math.round(value * 10) / 10 }));
});

const constructionByProjectData = computed(() => {
    return (constructionSection.value?.project_entries || [])
        .filter(e => e.size_mw)
        .sort((a, b) => (Number(b.size_mw) || 0) - (Number(a.size_mw) || 0))
        .slice(0, 15)
        .map(e => ({ label: (e.name || 'Unknown').substring(0, 25), value: Number(e.size_mw) || 0 }));
});

const capacityByStatusData = computed(() => {
    const statusMap = {
        completed_solar: 'Completed',
        construction_projects: 'Under Construction',
        transmission_projects: 'Transmission',
    };
    const groups = {};
    for (const s of sections.value) {
        if (s.section_type === 'net_metering') continue;
        const label = statusMap[s.section_type] || s.section_type;
        const mw = (s.project_entries || []).reduce((sum, e) => sum + (Number(e.size_mw) || 0), 0);
        groups[label] = (groups[label] || 0) + mw;
    }
    return Object.entries(groups).map(([name, value]) => ({ name, value: Math.round(value * 10) / 10 }));
});

// ── Drill-down ────────────────────────────────────────────

function handleDrill(dimension, params) {
    const label = params?.name || params;
    if (!label) return;
    activeDrillFilter.value = { dimension, value: label };
}

function clearDrill() {
    activeDrillFilter.value = null;
}

const filteredEntries = computed(() => {
    if (!activeDrillFilter.value) return allProjectEntries.value;
    const { dimension, value } = activeDrillFilter.value;

    if (dimension === 'status') {
        const typeMap = {
            'Completed': 'completed_solar',
            'Under Construction': 'construction_projects',
            'Transmission': 'transmission_projects',
        };
        const st = typeMap[value];
        return allProjectEntries.value.filter(e => e._sectionType === st);
    }

    return allProjectEntries.value.filter(e => {
        const entryVal = e[dimension] || 'Other';
        return entryVal === value;
    });
});

const breadcrumbItems = computed(() => {
    const items = [
        { label: 'Weekly Reports', href: !props.readOnly ? '/pp/weekly-reports' : '/weekly-reports' },
        { label: `Week ${props.report.week_number}`, href: '' },
    ];
    return items;
});

// ── Helpers ───────────────────────────────────────────────

function formatDate(d) {
    if (!d) return '—';
    return new Date(d).toLocaleDateString('en-GB', { day: 'numeric', month: 'long', year: 'numeric' });
}
</script>
