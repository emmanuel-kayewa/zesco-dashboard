<template>
    <div ref="chartEl" :style="{ width: '100%', height: height }"></div>
</template>

<script setup>
import { ref, onMounted, onUnmounted, watch, nextTick } from 'vue';
import Highcharts from 'highcharts';
import Highcharts3D from 'highcharts/highcharts-3d';
import { useDarkMode } from '@/Composables/useDarkMode';

// Enable 3D module (handle ESM/CJS interop + HMR)
const init3d = (Highcharts3D && typeof Highcharts3D === 'object' && 'default' in Highcharts3D)
    ? Highcharts3D.default
    : Highcharts3D;

if (!Highcharts.__zescoHighcharts3d && typeof init3d === 'function') {
    init3d(Highcharts);
    Highcharts.__zescoHighcharts3d = true;
}

const props = defineProps({
    data: { type: Array, default: () => [] },       // [{ name, value, color? }]
    colors: {
        type: Array,
        default: () => ['#3b82f6', '#22c55e', '#f59e0b', '#ef4444', '#8b5cf6', '#ec4899', '#06b6d4', '#f97316'],
    },
    height: { type: String, default: '400px' },
    title: { type: String, default: '' },
    innerSize: { type: String, default: '0%' },
    depth: { type: Number, default: 45 },
    alpha: { type: Number, default: 45 },
    beta: { type: Number, default: 0 },
});

const chartEl = ref(null);
const { isDark } = useDarkMode();
let chart = null;
let resizeObserver = null;

function buildOptions() {
    const seriesData = props.data.map((d, i) => ({
        name: d.name,
        y: d.value,
        color: d.color || props.colors[i % props.colors.length],
    }));

    const textColor = isDark.value ? '#cbd5e1' : '#334155';

    return {
        chart: {
            type: 'pie',
            backgroundColor: 'transparent',
            options3d: {
                enabled: true,
                alpha: props.alpha,
                beta: props.beta,
            },
            style: { fontFamily: 'inherit' },
        },
        title: {
            text: props.title || null,
            style: { color: textColor, fontSize: '14px', fontWeight: '600' },
        },
        tooltip: {
            pointFormat: '<b>{point.y}</b> ({point.percentage:.1f}%)',
            backgroundColor: isDark.value ? 'rgba(30, 41, 59, 0.95)' : 'rgba(255,255,255,0.95)',
            borderColor: isDark.value ? '#475569' : '#e2e8f0',
            style: { color: textColor, fontSize: '13px' },
        },
        plotOptions: {
            pie: {
                innerSize: props.innerSize,
                depth: props.depth,
                allowPointSelect: true,
                cursor: 'pointer',
                dataLabels: {
                    enabled: true,
                    format: '<b>{point.name}</b>: {point.y} ({point.percentage:.1f}%)',
                    style: {
                        color: textColor,
                        fontSize: '11px',
                        fontWeight: '500',
                        textOutline: 'none',
                    },
                    connectorColor: isDark.value ? '#64748b' : '#94a3b8',
                },
                showInLegend: true,
                borderWidth: 1,
                borderColor: isDark.value ? '#1e293b' : '#ffffff',
            },
        },
        legend: {
            align: 'center',
            verticalAlign: 'bottom',
            layout: 'horizontal',
            itemStyle: { color: textColor, fontSize: '12px', fontWeight: '400' },
            itemHoverStyle: { color: isDark.value ? '#f1f5f9' : '#0f172a' },
        },
        series: [{
            name: 'Received',
            data: seriesData,
        }],
        credits: { enabled: false },
    };
}

function initChart() {
    if (!chartEl.value) return;
    if (chart) chart.destroy();
    chart = Highcharts.chart(chartEl.value, buildOptions());
}

onMounted(() => {
    nextTick(() => {
        initChart();
        if (chartEl.value) {
            resizeObserver = new ResizeObserver(() => chart?.reflow());
            resizeObserver.observe(chartEl.value);
        }
    });
});

watch(() => [props.data, props.colors, props.depth, props.alpha, isDark.value], () => {
    nextTick(() => initChart());
}, { deep: true });

onUnmounted(() => {
    resizeObserver?.disconnect();
    chart?.destroy();
});
</script>
