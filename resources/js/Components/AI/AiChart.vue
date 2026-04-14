<template>
    <div v-if="chartOption" class="rounded-xl border border-gray-100 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-800/50 p-3">
        <p v-if="title" class="text-[11px] font-semibold uppercase tracking-[0.18em] text-gray-500 dark:text-gray-400 mb-2">{{ title }}</p>
        <BaseChart :option="chartOption" :height="height" />
    </div>
</template>

<script setup>
import { computed } from 'vue';
import BaseChart from '@/Components/Charts/BaseChart.vue';
import { useDarkMode } from '@/Composables/useDarkMode';
import { useChartPalettes } from '@/Composables/useChartPalettes';

const props = defineProps({
    /**
     * Chart spec from AI:
     * { type: 'bar'|'horizontal_bar'|'line'|'pie'|'gauge',
     *   title?: string,
     *   labels: string[],
     *   values: number[],
     *   value?: number,        // for gauge
     *   max?: number,          // for gauge
     *   unit?: string,         // for gauge
     *   series_name?: string }
     */
    chart: { type: Object, required: true },
    height: { type: String, default: '200px' },
});

const { isDark } = useDarkMode();
const { categorical } = useChartPalettes();

const colors = computed(() => categorical.value || ['#64748b']);

const title = computed(() => props.chart.title || '');

const axisLabelStyle = computed(() => ({
    color: isDark.value ? '#94a3b8' : '#64748b',
    fontSize: 10,
}));

const splitLineStyle = computed(() => ({
    lineStyle: { color: isDark.value ? '#334155' : '#f1f5f9', type: 'dashed' },
}));

const chartOption = computed(() => {
    const c = props.chart;
    if (!c || !c.type) return null;

    const type = normalizeChartType(c.type);

    if (type === 'bar' || type === 'horizontal_bar') {
        return buildBarOption(c, type === 'horizontal_bar');
    }
    if (type === 'line') {
        return buildLineOption(c);
    }
    if (type === 'pie' || type === 'donut') {
        return buildPieOption(c, type);
    }
    if (type === 'gauge') {
        return buildGaugeOption(c);
    }

    return null;
});

function normalizeChartType(raw) {
    if (!raw) return '';
    return String(raw)
        .trim()
        .toLowerCase()
        .replace(/[\s-]+/g, '_')
        .replace(/[^a-z0-9_]/g, '');
}

function seriesName(c, fallback = 'Value') {
    return c.series_name || c.seriesName || c.series || fallback;
}

function toNumber(v, fallback = 0) {
    const n = typeof v === 'number' ? v : Number(v);
    return Number.isFinite(n) ? n : fallback;
}

function buildBarOption(c, horizontal) {
    const labels = c.labels || [];
    const values = c.values || [];

    const data = values.map((v, i) => ({
        value: toNumber(v, 0),
        itemStyle: {
            color: colors.value[i % colors.value.length],
            borderRadius: horizontal ? [0, 4, 4, 0] : [4, 4, 0, 0],
        },
    }));

    return {
        tooltip: {
            trigger: 'axis',
            axisPointer: { type: 'shadow' },
            textStyle: { fontSize: 12 },
        },
        grid: { left: horizontal ? '30%' : '8%', right: '4%', top: '8%', bottom: '12%', containLabel: !horizontal },
        xAxis: {
            type: horizontal ? 'value' : 'category',
            data: horizontal ? undefined : labels,
            axisLabel: axisLabelStyle.value,
            axisLine: { lineStyle: { color: isDark.value ? '#475569' : '#cbd5e1' } },
            splitLine: horizontal ? splitLineStyle.value : undefined,
        },
        yAxis: {
            type: horizontal ? 'category' : 'value',
            data: horizontal ? labels : undefined,
            axisLabel: axisLabelStyle.value,
            axisLine: { show: false },
            splitLine: horizontal ? undefined : splitLineStyle.value,
        },
        series: [{
            name: seriesName(c),
            type: 'bar',
            data,
            barMaxWidth: 28,
            animationDuration: 600,
        }],
    };
}

function buildLineOption(c) {
    const labels = c.labels || [];
    const values = (c.values || []).map(v => toNumber(v, 0));
    const color = colors.value[0];

    return {
        tooltip: {
            trigger: 'axis',
            textStyle: { fontSize: 12 },
        },
        grid: { left: '8%', right: '4%', top: '8%', bottom: '12%', containLabel: true },
        xAxis: {
            type: 'category',
            data: labels,
            axisLabel: axisLabelStyle.value,
            axisLine: { lineStyle: { color: isDark.value ? '#475569' : '#cbd5e1' } },
        },
        yAxis: {
            type: 'value',
            axisLabel: axisLabelStyle.value,
            axisLine: { show: false },
            splitLine: splitLineStyle.value,
        },
        series: [{
            name: seriesName(c),
            type: 'line',
            data: values,
            smooth: true,
            symbol: 'circle',
            symbolSize: 5,
            lineStyle: { width: 2, color },
            itemStyle: { color },
            areaStyle: {
                color: {
                    type: 'linear',
                    x: 0, y: 0, x2: 0, y2: 1,
                    colorStops: [
                        { offset: 0, color: color + '30' },
                        { offset: 1, color: color + '05' },
                    ],
                },
            },
            animationDuration: 800,
        }],
    };
}

function buildPieOption(c, normalizedType = 'pie') {
    const labels = c.labels || [];
    const values = c.values || [];
    const data = labels.map((name, i) => ({
        name,
        value: toNumber(values[i], 0),
    }));

    return {
        tooltip: {
            trigger: 'item',
            formatter: '{b}: {c} ({d}%)',
            textStyle: { fontSize: 12 },
        },
        legend: {
            orient: 'horizontal',
            bottom: 0,
            textStyle: { color: isDark.value ? '#94a3b8' : '#64748b', fontSize: 10 },
        },
        color: colors.value,
        series: [{
            type: 'pie',
            radius: normalizedType === 'donut' ? ['40%', '65%'] : ['0%', '65%'],
            center: ['50%', '45%'],
            itemStyle: {
                borderRadius: 4,
                borderColor: isDark.value ? '#1e293b' : '#fff',
                borderWidth: 2,
            },
            label: { show: false },
            emphasis: {
                label: { show: true, fontSize: 11, fontWeight: 600 },
            },
            data,
            animationDuration: 600,
        }],
    };
}

function buildGaugeOption(c) {
    const value = toNumber(c.value ?? 0, 0);
    const max = toNumber(c.max ?? 100, 100);
    const unit = c.unit ?? '%';

    return {
        series: [{
            type: 'gauge',
            startAngle: 210,
            endAngle: -30,
            min: 0,
            max,
            pointer: {
                length: '55%',
                width: 3,
                itemStyle: { color: isDark.value ? '#cbd5e1' : '#334155' },
            },
            progress: {
                show: true,
                width: 10,
                roundCap: true,
                itemStyle: { color: colors.value[0] },
            },
            axisLine: {
                lineStyle: {
                    width: 10,
                    color: [[0.6, '#dc2626'], [0.8, '#f59e0b'], [1, '#22c55e']],
                },
                roundCap: true,
            },
            axisTick: { show: false },
            splitLine: { show: false },
            axisLabel: { show: false },
            detail: {
                valueAnimation: true,
                formatter: `{value}${unit}`,
                fontSize: 18,
                fontWeight: 600,
                color: isDark.value ? '#cbd5e1' : '#334155',
                offsetCenter: [0, '70%'],
            },
            title: {
                text: seriesName(c, ''),
                fontSize: 11,
                color: isDark.value ? '#94a3b8' : '#64748b',
                offsetCenter: [0, '90%'],
            },
            data: [{ value }],
            animationDuration: 800,
        }],
    };
}
</script>
