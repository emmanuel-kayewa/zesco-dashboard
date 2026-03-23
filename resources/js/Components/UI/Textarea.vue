<template>
    <div>
        <label v-if="label" :for="id" class="block mb-1.5 text-sm font-medium text-zinc-700 dark:text-gray-300">
            {{ label }}
            <span v-if="required" class="text-red-600">*</span>
        </label>
        <div class="relative">
            <textarea
                :id="id"
                :value="modelValue"
                @input="$emit('update:modelValue', $event.target.value)"
                @blur="$emit('blur', $event)"
                @focus="$emit('focus', $event)"
                :placeholder="placeholder"
                :required="required"
                :disabled="disabled"
                :readonly="readonly"
                :rows="rows"
                :class="textareaClasses"
                v-bind="$attrs"
            />
            <!-- Suffix slot for inline action buttons (e.g. submit icon) -->
            <div v-if="$slots.suffix" class="absolute right-2 top-1/2 -translate-y-1/2">
                <slot name="suffix" />
            </div>
        </div>
        <div v-if="helpText && !error" class="mt-2 text-sm text-gray-500 dark:text-gray-400">
            {{ helpText }}
        </div>
        <div v-if="error" class="mt-2 text-sm text-red-600 dark:text-red-500">
            {{ error }}
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';

defineOptions({ inheritAttrs: false });

const props = defineProps({
    modelValue: {
        type: [String, Number],
        default: ''
    },
    label: {
        type: String,
        default: ''
    },
    placeholder: {
        type: String,
        default: ''
    },
    required: {
        type: Boolean,
        default: false
    },
    disabled: {
        type: Boolean,
        default: false
    },
    readonly: {
        type: Boolean,
        default: false
    },
    error: {
        type: String,
        default: ''
    },
    helpText: {
        type: String,
        default: ''
    },
    size: {
        type: String,
        default: 'md',
        validator: (value) => ['sm', 'md', 'lg'].includes(value)
    },
    rows: {
        type: [String, Number],
        default: 3
    },
    resize: {
        type: String,
        default: 'vertical',
        validator: (value) => ['none', 'vertical', 'horizontal', 'both'].includes(value)
    },
    id: {
        type: String,
        default: () => `textarea-${Math.random().toString(36).substr(2, 9)}`
    }
});

defineEmits(['update:modelValue', 'blur', 'focus']);

const textareaClasses = computed(() => {
    const baseClasses = 'block w-full border rounded-lg shadow-sm transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 dark:focus:ring-offset-gray-900';

    const sizeClasses = {
        sm: 'px-3 py-1.5 text-xs',
        md: 'px-3 py-2 text-sm',
        lg: 'px-4 py-3 text-base'
    };

    const resizeClasses = {
        none: 'resize-none',
        vertical: 'resize-y',
        horizontal: 'resize-x',
        both: 'resize'
    };

    const focusClasses = 'focus:ring-zinc-900 focus:border-zinc-900 dark:focus:ring-white dark:focus:border-white';

    let stateClasses = '';
    if (props.error) {
        stateClasses = 'bg-red-50 border-red-500 text-red-900 placeholder-red-700 dark:bg-gray-800 dark:text-red-400 dark:border-red-500';
    } else if (props.disabled) {
        stateClasses = 'bg-gray-50 border-zinc-200 text-zinc-700 opacity-50 cursor-not-allowed dark:bg-gray-700 dark:border-gray-600 dark:text-gray-400';
    } else {
        stateClasses = 'bg-white border-zinc-200 border-b-zinc-300/80 text-zinc-700 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-200 dark:placeholder-gray-500';
    }

    return [
        baseClasses,
        sizeClasses[props.size],
        resizeClasses[props.resize],
        focusClasses,
        stateClasses
    ].join(' ');
});
</script>
