<template>
    <div class="flex flex-col items-center justify-center" :class="sizeClass">
        <div class="relative w-full" style="padding-bottom: 100%;">
            <svg class="absolute inset-0 w-full h-full" viewBox="0 0 120 120">
                <!-- Background track -->
                <circle
                    cx="60" cy="60" :r="radius"
                    fill="none"
                    :stroke="isDark ? '#374151' : '#e5e7eb'"
                    :stroke-width="strokeWidth"
                    stroke-linecap="round"
                />
                <!-- Progress arc -->
                <circle
                    cx="60" cy="60" :r="radius"
                    fill="none"
                    :stroke="progressColor"
                    :stroke-width="strokeWidth"
                    stroke-linecap="round"
                    :stroke-dasharray="circumference"
                    :stroke-dashoffset="dashOffset"
                    class="transition-all duration-700 ease-out"
                    transform="rotate(-90 60 60)"
                />
                <!-- Value text -->
                <text x="60" y="56" text-anchor="middle" dominant-baseline="central"
                      :class="isDark ? 'fill-gray-100' : 'fill-gray-800'"
                      font-size="22" font-weight="700">
                    {{ displayValue }}{{ unit }}
                </text>
                <!-- Label text -->
                <text v-if="label" x="60" y="76" text-anchor="middle" dominant-baseline="central"
                      :class="isDark ? 'fill-gray-400' : 'fill-gray-500'"
                      font-size="8" font-weight="500">
                    {{ label }}
                </text>
                <!-- Sublabel text -->
                <text v-if="sublabel" x="60" y="88" text-anchor="middle" dominant-baseline="central"
                      :class="isDark ? 'fill-gray-500' : 'fill-gray-400'"
                      font-size="7">
                    {{ sublabel }}
                </text>
            </svg>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';
import { useDarkMode } from '@/Composables/useDarkMode';

const { isDark } = useDarkMode();

const props = defineProps({
    value: { type: Number, default: 0 },
    max: { type: Number, default: 100 },
    min: { type: Number, default: 0 },
    label: { type: String, default: '' },
    sublabel: { type: String, default: '' },
    unit: { type: String, default: '%' },
    sizeClass: { type: String, default: 'w-full max-w-[200px]' },
    strokeWidth: { type: Number, default: 8 },
    thresholds: {
        type: Array,
        default: () => [
            [0.3, '#cf6060'],
            [0.6, '#d4a24e'],
            [1, '#4ead7a'],
        ],
    },
});

const radius = computed(() => (120 - props.strokeWidth) / 2);
const circumference = computed(() => 2 * Math.PI * radius.value);

const clampedValue = computed(() => Math.min(Math.max(props.value, props.min), props.max));
const ratio = computed(() => (clampedValue.value - props.min) / (props.max - props.min || 1));

const dashOffset = computed(() => circumference.value * (1 - ratio.value));

const displayValue = computed(() => Math.round(clampedValue.value));

const progressColor = computed(() => {
    for (const [threshold, color] of props.thresholds) {
        if (ratio.value <= threshold) return color;
    }
    return props.thresholds[props.thresholds.length - 1]?.[1] || '#4ead7a';
});
</script>
