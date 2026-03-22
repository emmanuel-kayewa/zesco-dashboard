<template>
    <AppLayout :directorates="directorates">
        <template #title>Executive Overview</template>

        <!-- Compact Header Bar -->
        <div class="flex flex-wrap items-center justify-between gap-3 mb-6 no-print">
            <div class="flex items-center gap-3">
                <!-- Tab Switcher -->
                <div class="flex items-center bg-gray-100 dark:bg-gray-700/50 rounded-lg p-0.5">
                    <button
                        v-for="tab in tabs"
                        :key="tab.key"
                        @click="activeTab = tab.key"
                        :class="[
                            'px-3 py-1.5 text-xs font-medium rounded-md transition-all duration-200',
                            activeTab === tab.key
                                ? 'bg-white dark:bg-gray-600 text-gray-900 dark:text-white shadow-sm'
                                : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300'
                        ]"
                    >
                        {{ tab.label }}
                    </button>
                </div>
            </div>
            <div class="flex items-center gap-1.5">
                <a href="/export/executive/pdf" class="p-1.5 rounded-lg text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors" title="Download PDF">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                </a>
                <a href="/export/executive/excel" class="p-1.5 rounded-lg text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors" title="Download Excel">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                </a>
                <button @click="printView" class="p-1.5 rounded-lg text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors" title="Print">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" /></svg>
                </button>
            </div>
        </div>

        <!-- ═══════════════════════════════════════════════════════ -->
        <!-- TAB: Overview                                          -->
        <!-- ═══════════════════════════════════════════════════════ -->
        <div v-show="activeTab === 'overview'">

            <!-- KPI Summary Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                <KpiCard
                    title="Total Projects"
                    :formattedValue="String(portfolio.totalProjects)"
                    :change="null"
                    status="healthy"
                />
                <KpiCard
                    title="Portfolio Committed"
                    :formattedValue="fmtUsd(portfolio.totalCommitted)"
                    :change="null"
                    status="healthy"
                />
                <KpiCard
                    title="Average Progress"
                    :formattedValue="portfolio.avgProgress + '%'"
                    :change="null"
                    :status="portfolio.avgProgress >= 50 ? 'healthy' : 'warning'"
                />
                <KpiCard
                    title="Risk Exposure"
                    :formattedValue="portfolio.highRisks + ' High / ' + portfolio.totalRisks + ' Total'"
                    :change="null"
                    :status="portfolio.highRisks > 5 ? 'critical' : portfolio.highRisks > 0 ? 'warning' : 'healthy'"
                />
            </div>

            <!-- Charts Row 1: Sector Investment + Portfolio Cost -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <ChartCard title="Investment by Sector (USD)" :baseHeight="340">
                    <template #default="{ zoomedHeight }">
                        <BarChart
                            :data="sectorInvestmentData"
                            xField="sector"
                            yField="committed"
                            seriesName="Committed (USD)"
                            :height="zoomedHeight"
                            :multiSeries="[
                                { name: 'Committed', field: 'committed', color: '#6889c4' },
                                { name: 'Paid', field: 'paid', color: '#5ba5b5' },
                            ]"
                        />
                    </template>
                </ChartCard>

                <ChartCard title="Portfolio Cost: Committed vs Paid" :baseHeight="340">
                    <template #default>
                        <Pie3DChart
                            :data="portfolioCostPie"
                            height="340px"
                            :depth="35"
                        />
                    </template>
                </ChartCard>
            </div>

            <!-- Charts Row 2: Projects by Sector + Risks by Category -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <ChartCard title="Projects by Sector" :baseHeight="320">
                    <template #default>
                        <Pie3DChart
                            :data="charts.sectorBreakdown"
                            height="320px"
                            :depth="35"
                        />
                    </template>
                </ChartCard>

                <ChartCard title="Risks by Category" :baseHeight="320">
                    <template #default>
                        <Pie3DChart
                            :data="charts.risksByCategory"
                            height="320px"
                            :depth="35"
                        />
                    </template>
                </ChartCard>
            </div>

            <!-- Top Issues -->
            <Card v-if="topIssues.length > 0" title="Attention Required" class="mb-6">
                <div class="divide-y divide-gray-100 dark:divide-gray-700/50">
                    <div v-for="issue in topIssues" :key="issue.id" class="flex items-center gap-3 px-4 py-3">
                        <span class="w-2.5 h-2.5 rounded-full flex-shrink-0"
                              :class="{
                                  'bg-red-500': issue.rag === 'Red',
                                  'bg-amber-500': issue.rag === 'Amber',
                                  'bg-green-500': issue.rag === 'Green',
                                  'bg-gray-400': !issue.rag,
                              }"></span>
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-medium text-gray-900 dark:text-white truncate">
                                {{ issue.code }} &mdash; {{ issue.name }}
                            </p>
                            <p v-if="issue.key_issue" class="text-xs text-gray-500 dark:text-gray-400 truncate mt-0.5">
                                {{ issue.key_issue }}
                            </p>
                        </div>
                        <div class="text-right flex-shrink-0">
                            <p class="text-xs text-gray-400">{{ issue.sector }}</p>
                            <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ issue.progress ?? 0 }}%</p>
                        </div>
                    </div>
                </div>
            </Card>

            <!-- Directorates Grid -->
            <Card title="Directorate Performance" class="mb-6">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4 p-2">
                    <Link
                        v-for="d in directorateSummaries"
                        :key="d.id"
                        :href="`/dashboard/directorate/${d.slug}`"
                        class="block p-4 rounded-xl border border-gray-100 dark:border-gray-700 hover:shadow-executive-md hover:border-[var(--palette-accent-lighter)] dark:hover:border-[var(--palette-accent-dark)] transition-all duration-200"
                    >
                        <div class="flex items-center gap-3 mb-3">
                            <div class="w-3 h-3 rounded-full" :style="{ backgroundColor: d.color }"></div>
                            <h4 class="text-sm font-semibold text-gray-900 dark:text-white truncate">{{ d.code }}</h4>
                            <span v-if="!d.has_data" class="ml-auto text-[10px] text-gray-400 bg-gray-100 dark:bg-gray-700 px-1.5 py-0.5 rounded">No data yet</span>
                        </div>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mb-3 truncate" :title="d.name">{{ d.name }}</p>
                        <template v-if="d.has_data">
                            <div class="grid grid-cols-2 gap-2 text-xs">
                                <div>
                                    <p class="text-gray-400">Projects</p>
                                    <p class="font-semibold text-gray-900 dark:text-white">{{ d.project_count }}</p>
                                </div>
                                <div>
                                    <p class="text-gray-400">Avg Progress</p>
                                    <p class="font-semibold text-gray-900 dark:text-white">{{ d.avg_progress }}%</p>
                                </div>
                                <div>
                                    <p class="text-gray-400">Committed</p>
                                    <p class="font-semibold text-gray-900 dark:text-white">{{ fmtUsd(d.total_committed) }}</p>
                                </div>
                                <div>
                                    <p class="text-gray-400">Risks</p>
                                    <p class="font-semibold" :class="d.high_risk_count > 2 ? 'text-red-600' : 'text-gray-900 dark:text-white'">
                                        {{ d.risk_count }} ({{ d.high_risk_count }} high)
                                    </p>
                                </div>
                            </div>
                        </template>
                        <template v-else>
                            <div class="flex items-center justify-center py-4">
                                <p class="text-xs text-gray-400 dark:text-gray-500 italic">Data collection pending</p>
                            </div>
                        </template>
                    </Link>
                </div>
            </Card>
        </div>

        <!-- ═══════════════════════════════════════════════════════ -->
        <!-- TAB: Comparison                                        -->
        <!-- ═══════════════════════════════════════════════════════ -->
        <div v-show="activeTab === 'comparison'">

            <!-- Metric Selector -->
            <div class="flex flex-wrap items-center gap-4 mb-6">
                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Compare by:</span>
                <div class="flex items-center bg-gray-100 dark:bg-gray-700/50 rounded-lg p-0.5">
                    <button
                        v-for="m in comparisonMetrics"
                        :key="m.key"
                        @click="selectedMetric = m.key"
                        :class="[
                            'px-3 py-1.5 text-xs font-medium rounded-md transition-all duration-200',
                            selectedMetric === m.key
                                ? 'bg-white dark:bg-gray-600 text-gray-900 dark:text-white shadow-sm'
                                : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300'
                        ]"
                    >
                        {{ m.label }}
                    </button>
                </div>
            </div>

            <!-- Comparison Bar Chart -->
            <ChartCard title="Directorate Comparison" class="mb-6" :baseHeight="380">
                <template #default="{ zoomedHeight }">
                    <BarChart
                        :data="comparisonBarData"
                        xField="code"
                        :yField="selectedMetric"
                        :seriesName="comparisonMetrics.find(m => m.key === selectedMetric)?.label || ''"
                        :horizontal="true"
                        :height="zoomedHeight"
                    />
                </template>
            </ChartCard>

            <!-- Comparison Table -->
            <Card title="Detailed Comparison" class="mb-6">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm min-w-[640px]">
                        <thead>
                            <tr class="border-b border-gray-200 dark:border-gray-700">
                                <th class="text-left py-3 px-4 text-xs font-semibold text-gray-500 uppercase">Directorate</th>
                                <th class="text-right py-3 px-4 text-xs font-semibold text-gray-500 uppercase">Projects</th>
                                <th class="text-right py-3 px-4 text-xs font-semibold text-gray-500 uppercase hidden sm:table-cell">Committed (USD)</th>
                                <th class="text-right py-3 px-4 text-xs font-semibold text-gray-500 uppercase hidden md:table-cell">Paid (USD)</th>
                                <th class="text-right py-3 px-4 text-xs font-semibold text-gray-500 uppercase">Avg Progress</th>
                                <th class="text-right py-3 px-4 text-xs font-semibold text-gray-500 uppercase">Risks</th>
                                <th class="text-center py-3 px-4 text-xs font-semibold text-gray-500 uppercase">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="d in directorateSummaries" :key="d.id" class="border-b border-gray-100 dark:border-gray-700/50 hover:bg-gray-50 dark:hover:bg-gray-700/20 transition">
                                <td class="py-3 px-4">
                                    <Link :href="`/dashboard/directorate/${d.slug}`" class="font-medium text-zesco-700 dark:text-zesco-400 hover:underline truncate block max-w-[200px]" :title="`${d.code} — ${d.name}`">
                                        {{ d.code }} &mdash; {{ d.name }}
                                    </Link>
                                </td>
                                <td class="text-right py-3 px-4 font-medium">
                                    {{ d.has_data ? d.project_count : '—' }}
                                </td>
                                <td class="text-right py-3 px-4 hidden sm:table-cell">
                                    {{ d.has_data ? fmtUsd(d.total_committed) : '—' }}
                                </td>
                                <td class="text-right py-3 px-4 hidden md:table-cell">
                                    {{ d.has_data ? fmtUsd(d.total_paid) : '—' }}
                                </td>
                                <td class="text-right py-3 px-4 font-medium" :class="d.has_data ? (d.avg_progress >= 50 ? 'text-green-600' : 'text-amber-600') : ''">
                                    {{ d.has_data ? d.avg_progress + '%' : '—' }}
                                </td>
                                <td class="text-right py-3 px-4">
                                    <span v-if="d.has_data" :class="d.high_risk_count > 2 ? 'text-red-600 font-semibold' : ''">
                                        {{ d.risk_count }} ({{ d.high_risk_count }} high)
                                    </span>
                                    <span v-else class="text-gray-400">&mdash;</span>
                                </td>
                                <td class="text-center py-3 px-4">
                                    <span v-if="d.has_data" class="inline-block w-2.5 h-2.5 rounded-full"
                                          :class="{
                                              'bg-green-500': d.avg_progress >= 50,
                                              'bg-amber-500': d.avg_progress >= 25 && d.avg_progress < 50,
                                              'bg-red-500': d.avg_progress < 25,
                                          }"></span>
                                    <span v-else class="inline-block w-2.5 h-2.5 rounded-full bg-gray-300 dark:bg-gray-600"></span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </Card>
        </div>

    </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import AppLayout from '@/Components/Layout/AppLayout.vue';
import KpiCard from '@/Components/Dashboard/KpiCard.vue';
import Card from '@/Components/UI/Card.vue';
import ChartCard from '@/Components/UI/ChartCard.vue';
import BarChart from '@/Components/Charts/BarChart.vue';
import Pie3DChart from '@/Components/Charts/Pie3DChart.vue';


const props = defineProps({
    directorates: { type: Array, default: () => [] },
    directorateSummaries: { type: Array, default: () => [] },
    portfolio: { type: Object, default: () => ({}) },
    charts: { type: Object, default: () => ({}) },
    topIssues: { type: Array, default: () => [] },
});

// ── Tabs ──
const tabs = [
    { key: 'overview', label: 'Overview' },
    { key: 'comparison', label: 'Comparison' },
];
const activeTab = ref('overview');

// ── KPI helpers ──
function fmtUsd(val) {
    const n = Number(val) || 0;
    if (n >= 1e9) return '$' + (n / 1e9).toFixed(2) + 'B';
    if (n >= 1e6) return '$' + (n / 1e6).toFixed(1) + 'M';
    if (n >= 1e3) return '$' + (n / 1e3).toFixed(1) + 'K';
    return '$' + n.toLocaleString();
}

function printView() {
    window.print();
}

// ── Chart data: Portfolio Cost Pie (Committed vs Paid) ──
const portfolioCostPie = computed(() => {
    const committed = props.portfolio.totalCommitted || 0;
    const paid = props.portfolio.totalPaid || 0;
    const remaining = Math.max(0, committed - paid);
    return [
        { name: 'Paid to Date', value: paid, color: '#5ba5b5' },
        { name: 'Remaining', value: remaining, color: '#6889c4' },
    ];
});

// ── Chart data: Sector Investment Bar ──
const sectorInvestmentData = computed(() => {
    return (props.charts.sectorBreakdown || []).map(s => ({
        sector: s.name,
        committed: s.totalCost,
        paid: s.totalPaid || 0,
    }));
});

// ── Comparison Tab ──
const comparisonMetrics = [
    { key: 'project_count', label: 'Projects' },
    { key: 'total_committed', label: 'Committed (USD)' },
    { key: 'avg_progress', label: 'Avg Progress' },
    { key: 'risk_count', label: 'Risks' },
];
const selectedMetric = ref('project_count');

const comparisonBarData = computed(() => {
    return props.directorateSummaries.map(d => ({
        code: d.code,
        project_count: d.project_count || 0,
        total_committed: d.total_committed || 0,
        avg_progress: d.avg_progress || 0,
        risk_count: d.risk_count || 0,
    }));
});
</script>
