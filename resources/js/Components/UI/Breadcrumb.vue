<template>
    <nav class="text-sm mb-6 no-print">
        <!-- Desktop: Show all items -->
        <ol class="hidden md:flex items-center gap-2 text-gray-500 dark:text-gray-400">
            <template v-for="(item, index) in items" :key="index">
                <li v-if="index > 0">/</li>
                <li class="flex items-center">
                    <Link 
                        v-if="item.href && !item.current" 
                        :href="item.href" 
                        class="hover:text-zesco-600 transition-colors whitespace-nowrap"
                    >
                        {{ item.label }}
                    </Link>
                    <span 
                        v-else 
                        class="font-medium text-gray-900 dark:text-white"
                        :class="item.truncate ? 'truncate max-w-xs block' : 'whitespace-nowrap'"
                    >
                        {{ item.label }}
                    </span>
                </li>
            </template>
        </ol>

        <!-- Mobile: Show first, overflow menu, and last 2 items -->
        <ol class="flex md:hidden items-center gap-2 text-gray-500 dark:text-gray-400">
            <!-- Always show first item (Home) -->
            <li v-if="items.length > 0" class="flex items-center shrink-0">
                <Link 
                    v-if="items[0].href && !items[0].current" 
                    :href="items[0].href" 
                    class="hover:text-zesco-600 transition-colors whitespace-nowrap"
                >
                    {{ items[0].label }}
                </Link>
                <span v-else class="font-medium text-gray-900 dark:text-white whitespace-nowrap">
                    {{ items[0].label }}
                </span>
            </li>

            <!-- Show overflow menu if there are hidden items -->
            <template v-if="middleItems.length > 0">
                <li class="shrink-0">/</li>
                <li class="relative shrink-0">
                    <button
                        @click="showDropdown = !showDropdown"
                        class="hover:text-zesco-600 transition-colors px-1 flex items-center"
                        type="button"
                        aria-label="Show hidden breadcrumb items"
                    >
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 16 16">
                            <circle cx="2" cy="8" r="1.5"/>
                            <circle cx="8" cy="8" r="1.5"/>
                            <circle cx="14" cy="8" r="1.5"/>
                        </svg>
                    </button>

                    <!-- Dropdown menu -->
                    <div 
                        v-if="showDropdown"
                        class="absolute left-0 top-full mt-1 py-2 bg-white dark:bg-gray-800 rounded-lg shadow-lg border border-gray-200 dark:border-gray-700 z-50 min-w-[200px]"
                    >
                        <Link
                            v-for="(item, index) in middleItems"
                            :key="index"
                            :href="item.href"
                            class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors truncate"
                            @click="showDropdown = false"
                        >
                            {{ item.label }}
                        </Link>
                    </div>
                </li>
            </template>

            <!-- Show last 2 items (previous + current) -->
            <template v-for="(item, index) in lastTwoItems" :key="`last-${index}`">
                <li class="shrink-0">/</li>
                <li class="flex items-center min-w-0">
                    <Link 
                        v-if="item.href && !item.current" 
                        :href="item.href" 
                        class="hover:text-zesco-600 transition-colors truncate block max-w-[120px]"
                    >
                        {{ item.label }}
                    </Link>
                    <span 
                        v-else 
                        class="font-medium text-gray-900 dark:text-white truncate block max-w-[120px]"
                    >
                        {{ item.label }}
                    </span>
                </li>
            </template>
        </ol>
    </nav>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    /**
     * Array of breadcrumb items
     * @type {Array<{label: string, href?: string, current?: boolean, truncate?: boolean}>}
     * @example
     * [
     *   { label: 'Dashboard', href: '/dashboard' },
     *   { label: 'PP Portfolio', href: '/pp/dashboard' },
     *   { label: 'Explorer', href: '/pp/dashboard/explore' },
     *   { label: 'Current Page', current: true }
     * ]
     */
    items: {
        type: Array,
        required: true,
        default: () => []
    }
});

const showDropdown = ref(false);

// For mobile: calculate middle items (items between first and last 2)
const middleItems = computed(() => {
    if (props.items.length <= 3) return [];
    return props.items.slice(1, -2).filter(item => item.href);
});

// Last 2 items for mobile
const lastTwoItems = computed(() => {
    if (props.items.length <= 1) return [];
    if (props.items.length === 2) return [props.items[1]];
    return props.items.slice(-2);
});

// Close dropdown when clicking outside
const handleClickOutside = (event) => {
    const dropdown = event.target.closest('[aria-label="Show hidden breadcrumb items"]');
    if (!dropdown && showDropdown.value) {
        showDropdown.value = false;
    }
};

onMounted(() => {
    document.addEventListener('click', handleClickOutside);
});

onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside);
});
</script>
