<template>
    <span :class="badgeClasses" :style="paletteStyle">
        <!-- Dot (for dot and filled-dot variants) -->
        <svg v-if="variant === 'dot' || variant === 'filled-dot'" class="size-1.5" :class="dotClasses" :style="paletteDotStyle" viewBox="0 0 6 6" aria-hidden="true">
            <circle cx="3" cy="3" r="3" />
        </svg>
        
        <!-- Label -->
        <slot>{{ label }}</slot>
    </span>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    variant: {
        type: String,
        default: 'dot',
        validator: (value) => ['filled', 'dot', 'filled-dot'].includes(value)
    },
    color: {
        type: String,
        default: 'gray',
        validator: (value) => ['gray', 'red', 'yellow', 'green', 'blue', 'indigo', 'purple', 'pink', 'orange', 'amber', 'palette'].includes(value)
    },
    label: {
        type: String,
        default: ''
    },
    dotColor: {
        type: String,
        default: null // If null, uses the same as color prop
    }
});

const isPalette = computed(() => props.color === 'palette');
const isDotPalette = computed(() => (props.dotColor || props.color) === 'palette');

const paletteStyle = computed(() => {
    if (!isPalette.value) return {};
    if (props.variant === 'filled') {
        return {
            backgroundColor: 'var(--palette-accent-lighter)',
            color: 'var(--palette-accent-dark)',
            '--tw-ring-color': 'var(--palette-accent-light)',
        };
    }
    if (props.variant === 'filled-dot') {
        return {
            backgroundColor: 'var(--palette-accent-lighter)',
            color: 'var(--palette-accent-dark)',
        };
    }
    return {};
});

const paletteDotStyle = computed(() => {
    if (!isDotPalette.value) return {};
    return { fill: 'var(--palette-accent)' };
});

const badgeClasses = computed(() => {
    const base = 'inline-flex items-center rounded-md px-2 py-1 text-xs font-medium text-nowrap';
    
    if (props.variant === 'filled') {
        return [base, isPalette.value ? 'ring-1 ring-inset' : filledColorClasses.value];
    } else if (props.variant === 'dot') {
        return [base, 'gap-x-1.5', dotVariantClasses.value];
    } else if (props.variant === 'filled-dot') {
        return [base, 'gap-x-1.5', isPalette.value ? '' : filledDotColorClasses.value];
    }
    
    return base;
});

const filledColorClasses = computed(() => {
    const colorMap = {
        gray: 'bg-gray-50 text-gray-600 ring-1 ring-inset ring-gray-500/10 dark:bg-gray-800 dark:text-gray-300 dark:ring-gray-700',
        red: 'bg-red-50 text-red-700 ring-1 ring-inset ring-red-600/10 dark:bg-red-900/30 dark:text-red-400 dark:ring-red-800',
        yellow: 'bg-yellow-50 text-yellow-800 ring-1 ring-inset ring-yellow-600/20 dark:bg-yellow-900/30 dark:text-yellow-400 dark:ring-yellow-800',
        amber: 'bg-amber-50 text-amber-800 ring-1 ring-inset ring-amber-600/20 dark:bg-amber-900/30 dark:text-amber-400 dark:ring-amber-800',
        green: 'bg-green-50 text-green-700 ring-1 ring-inset ring-green-600/20 dark:bg-green-900/30 dark:text-green-400 dark:ring-green-800',
        blue: 'bg-blue-50 text-blue-700 ring-1 ring-inset ring-blue-700/10 dark:bg-blue-900/30 dark:text-blue-400 dark:ring-blue-800',
        indigo: 'bg-indigo-50 text-indigo-700 ring-1 ring-inset ring-indigo-700/10 dark:bg-indigo-900/30 dark:text-indigo-400 dark:ring-indigo-800',
        purple: 'bg-purple-50 text-purple-700 ring-1 ring-inset ring-purple-700/10 dark:bg-purple-900/30 dark:text-purple-400 dark:ring-purple-800',
        pink: 'bg-pink-50 text-pink-700 ring-1 ring-inset ring-pink-700/10 dark:bg-pink-900/30 dark:text-pink-400 dark:ring-pink-800',
        orange: 'bg-orange-50 text-orange-700 ring-1 ring-inset ring-orange-600/20 dark:bg-orange-900/30 dark:text-orange-400 dark:ring-orange-800',
    };
    
    return colorMap[props.color] || colorMap.gray;
});

const dotVariantClasses = computed(() => {
    // Dot variant has neutral background with gray ring
    return 'text-gray-900 ring-1 ring-inset ring-gray-200 dark:bg-gray-800 dark:text-gray-100 dark:ring-gray-700';
});

const filledDotColorClasses = computed(() => {
    const colorMap = {
        gray: 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-300',
        red: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
        yellow: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400',
        amber: 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-400',
        green: 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
        blue: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
        indigo: 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-400',
        purple: 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400',
        pink: 'bg-pink-100 text-pink-700 dark:bg-pink-900/30 dark:text-pink-400',
        orange: 'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400',
    };
    
    return colorMap[props.color] || colorMap.gray;
});

const dotClasses = computed(() => {
    const effectiveColor = props.dotColor || props.color;
    
    const colorMap = {
        gray: 'fill-gray-400',
        red: 'fill-red-500',
        yellow: 'fill-yellow-500',
        amber: 'fill-amber-500',
        green: 'fill-green-500',
        blue: 'fill-blue-500',
        indigo: 'fill-indigo-500',
        purple: 'fill-purple-500',
        pink: 'fill-pink-500',
        orange: 'fill-orange-500',
    };
    
    return colorMap[effectiveColor] || colorMap.gray;
});
</script>
