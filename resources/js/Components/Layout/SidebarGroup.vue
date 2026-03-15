<template>
    <div>
        <!-- Group header (clickable to expand/collapse) -->
        <button
            @click="toggle"
            :class="['sidebar-link w-full', isActive && 'sidebar-link-active']"
            :title="title || label"
        >
            <!-- Initials -->
            <span v-if="initial" :class="[
                isActive ? 'border-gray-900 text-gray-900 dark:border-white dark:text-white' : 'border-gray-200 text-gray-400 group-hover:border-gray-900 group-hover:text-gray-900 dark:border-gray-700 dark:text-gray-500 dark:group-hover:text-gray-300 dark:group-hover:border-white',
                'flex size-6 shrink-0 items-center justify-center rounded-lg border bg-white dark:bg-gray-800 text-[8px] font-bold transition-colors duration-150'
            ]">
                {{ initial }}
            </span>
            <span v-else class="w-5 h-5 flex-shrink-0">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                </svg>
            </span>

            <span v-if="label" class="truncate flex-1 text-left group-hover:text-gray-900 dark:group-hover:text-white transition-colors duration-150">{{ label }}</span>
            <!-- Chevron -->
            <svg
                v-if="label"
                :class="['w-4 h-4 transition-transform duration-200 flex-shrink-0', expanded && 'rotate-90']"
                fill="none" viewBox="0 0 24 24" stroke="currentColor"
            >
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
            </svg>
        </button>

        <!-- Child links -->
        <transition
            enter-active-class="transition-all duration-200 ease-out"
            leave-active-class="transition-all duration-200 ease-in"
            enter-from-class="max-h-0 opacity-0"
            enter-to-class="max-h-96 opacity-100"
            leave-from-class="max-h-96 opacity-100"
            leave-to-class="max-h-0 opacity-0"
        >
            <div v-show="expanded" class="overflow-hidden ml-4 pl-3 border-l border-gray-200 dark:border-gray-700 space-y-0.5 mt-0.5">
                <slot />
            </div>
        </transition>
    </div>
</template>

<script setup>
import { ref, watch } from 'vue';

const props = defineProps({
    label:    { type: String, default: '' },
    initial:  { type: String, default: '' },
    title:    { type: String, default: '' },
    color:    { type: String, default: null },
    isActive: { type: Boolean, default: false },
});

const expanded = ref(props.isActive);

// Auto-expand when a child route becomes active
watch(() => props.isActive, (val) => {
    if (val) expanded.value = true;
});

function toggle() {
    expanded.value = !expanded.value;
}
</script>
