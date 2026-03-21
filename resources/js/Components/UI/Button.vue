<template>
    <button 
        :type="type"
        :disabled="disabled || loading"
        :class="buttonClasses"
        @click="$emit('click', $event)"
    >
        <svg v-if="loading" class="w-4 h-4 animate-spin" viewBox="0 0 24 24" fill="none">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
        </svg>
        <slot></slot>
    </button>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    variant: {
        type: String,
        default: 'primary', // primary, secondary, success, danger, warning, info, dark, light
        validator: (value) => ['primary', 'secondary', 'success', 'danger', 'warning', 'info', 'dark', 'light'].includes(value)
    },
    outline: {
        type: Boolean,
        default: false
    },
    size: {
        type: String,
        default: 'md', // xs, sm, md, lg, xl
        validator: (value) => ['xs', 'sm', 'md', 'lg', 'xl'].includes(value)
    },
    disabled: {
        type: Boolean,
        default: false
    },
    loading: {
        type: Boolean,
        default: false
    },
    type: {
        type: String,
        default: 'button'
    },
    pill: {
        type: Boolean,
        default: false
    }
});

defineEmits(['click']);

const buttonClasses = computed(() => {
    const baseClasses = 'inline-flex items-center justify-center gap-2 font-medium focus:outline-none focus:ring-4 transition-colors duration-150 whitespace-nowrap';
    
    // Size classes - matching form control heights for better alignment
    const sizeClasses = {
        xs: 'h-8 px-3 text-xs',
        sm: 'h-9 px-3 text-sm',
        md: 'h-10 px-5 text-sm',
        lg: 'h-12 px-5 text-base',
        xl: 'h-14 px-6 text-base'
    };
    
    // Border radius
    const roundedClasses = props.pill ? 'rounded-full' : 'rounded-lg';
    
    // Variant classes
    const variantClasses = {
        primary: props.outline 
            ? 'text-gray-900 hover:text-white border border-gray-800 hover:bg-gray-900 focus:ring-gray-300 dark:border-gray-400 dark:text-gray-300 dark:hover:text-gray-900 dark:hover:bg-white dark:focus:ring-gray-700'
            : 'text-white bg-gray-900 hover:bg-gray-950 focus:ring-gray-300 dark:bg-white dark:text-gray-900 dark:hover:bg-gray-100 dark:focus:ring-gray-700',
        secondary: props.outline
            ? 'text-gray-900 hover:text-white border border-gray-800 hover:bg-gray-900 focus:ring-gray-300 dark:border-gray-600 dark:text-gray-400 dark:hover:text-white dark:hover:bg-gray-600 dark:focus:ring-gray-800'
            : 'text-gray-900 bg-white border border-gray-300 hover:bg-gray-100 focus:ring-gray-200 dark:bg-gray-800 dark:text-white dark:border-gray-600 dark:hover:bg-gray-700 dark:hover:border-gray-600 dark:focus:ring-gray-700',
        success: props.outline
            ? 'text-green-700 hover:text-white border border-green-700 hover:bg-green-800 focus:ring-green-300 dark:border-green-500 dark:text-green-500 dark:hover:text-white dark:hover:bg-green-600 dark:focus:ring-green-800'
            : 'text-white bg-green-700 hover:bg-green-800 focus:ring-green-300 dark:bg-green-600 dark:hover:bg-green-700 dark:focus:ring-green-800',
        danger: props.outline
            ? 'text-red-700 hover:text-white border border-red-700 hover:bg-red-800 focus:ring-red-300 dark:border-red-500 dark:text-red-500 dark:hover:text-white dark:hover:bg-red-600 dark:focus:ring-red-900'
            : 'text-white bg-red-700 hover:bg-red-800 focus:ring-red-300 dark:bg-red-600 dark:hover:bg-red-700 dark:focus:ring-red-900',
        warning: props.outline
            ? 'text-yellow-400 hover:text-white border border-yellow-400 hover:bg-yellow-500 focus:ring-yellow-300 dark:border-yellow-300 dark:text-yellow-300 dark:hover:text-white dark:hover:bg-yellow-400 dark:focus:ring-yellow-900'
            : 'text-white bg-yellow-400 hover:bg-yellow-500 focus:ring-yellow-300 dark:focus:ring-yellow-900',
        info: props.outline
            ? 'text-cyan-700 hover:text-white border border-cyan-700 hover:bg-cyan-800 focus:ring-cyan-300 dark:border-cyan-500 dark:text-cyan-500 dark:hover:text-white dark:hover:bg-cyan-600 dark:focus:ring-cyan-800'
            : 'text-white bg-cyan-700 hover:bg-cyan-800 focus:ring-cyan-300 dark:bg-cyan-600 dark:hover:bg-cyan-700 dark:focus:ring-cyan-800',
        dark: props.outline
            ? 'text-gray-900 hover:text-white border border-gray-900 hover:bg-gray-900 focus:ring-gray-300 dark:border-gray-600 dark:text-gray-400 dark:hover:text-white dark:hover:bg-gray-600 dark:focus:ring-gray-800'
            : 'text-white bg-gray-900 hover:bg-gray-950 focus:ring-gray-300 dark:bg-gray-800 dark:hover:bg-gray-700 dark:focus:ring-gray-700 dark:border-gray-700',
        light: props.outline
            ? 'text-gray-900 hover:text-gray-900 bg-white border border-gray-300 hover:bg-gray-100 focus:ring-gray-200 dark:bg-gray-800 dark:text-white dark:border-gray-600 dark:hover:bg-gray-700 dark:hover:border-gray-600 dark:focus:ring-gray-700'
            : 'text-gray-900 bg-white border border-gray-300 hover:bg-gray-100 focus:ring-gray-200 dark:bg-gray-800 dark:text-white dark:border-gray-600 dark:hover:bg-gray-700 dark:hover:border-gray-600 dark:focus:ring-gray-700'
    };
    
    const disabledClasses = (props.disabled || props.loading) ? 'opacity-50 cursor-not-allowed' : '';
    
    return [
        baseClasses,
        sizeClasses[props.size],
        roundedClasses,
        variantClasses[props.variant],
        disabledClasses
    ].join(' ');
});
</script>
