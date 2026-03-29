<template>
    <div class="relative" ref="containerRef">
        <!-- Trigger Button (palette icon) -->
        <button
            type="button"
            @click="open = !open"
            class="p-2 rounded-lg text-gray-600 hover:text-gray-900 dark:text-gray-300 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-gray-700 transition"
            title="Chart colour palette"
        >
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M4.098 19.902a3.75 3.75 0 005.304 0l6.401-6.402M6.75 21A3.75 3.75 0 013 17.25V4.125C3 3.504 3.504 3 4.125 3h5.25c.621 0 1.125.504 1.125 1.125v4.072M6.75 21a3.75 3.75 0 003.75-3.75V8.197M6.75 21h13.125c.621 0 1.125-.504 1.125-1.125v-5.25c0-.621-.504-1.125-1.125-1.125h-4.072M10.5 8.197l2.88-2.88c.438-.439 1.15-.439 1.59 0l3.712 3.713c.44.44.44 1.152 0 1.59l-2.879 2.88M6.75 17.25h.008v.008H6.75v-.008z" />
            </svg>
        </button>

        <!-- Popover -->
        <transition
            enter-active-class="transition ease-out duration-150"
            enter-from-class="opacity-0 translate-y-1"
            enter-to-class="opacity-100 translate-y-0"
            leave-active-class="transition ease-in duration-100"
            leave-from-class="opacity-100 translate-y-0"
            leave-to-class="opacity-0 translate-y-1"
        >
            <div
                v-if="open"
                ref="popoverRef"
                :class="[
                    'top-full mt-2 z-50 rounded-xl bg-white dark:bg-gray-800 shadow-lg ring-1 ring-gray-200 dark:ring-gray-700 overflow-hidden',
                    isMobile ? 'fixed' : 'absolute right-0',
                    'w-72 max-w-[calc(100vw-2rem)]'
                ]"
                :style="popoverStyle"
            >
                <!-- Header -->
                <div class="px-4 py-2.5 border-b border-gray-100 dark:border-gray-700">
                    <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Chart palette</p>
                </div>

                <!-- Palette Options -->
                <div class="py-1.5 max-h-80 overflow-y-auto">
                    <button
                        v-for="option in paletteOptions"
                        :key="option.value"
                        type="button"
                        @click="selectPalette(option.value)"
                        class="w-full flex items-center gap-3 px-4 py-2.5 text-left transition-colors"
                        :class="[
                            paletteKey === option.value
                                ? 'bg-gray-50 dark:bg-gray-700/60'
                                : 'hover:bg-gray-50 dark:hover:bg-gray-700/40'
                        ]"
                    >
                        <!-- Active indicator -->
                        <div class="w-4 flex-shrink-0 flex items-center justify-center">
                            <svg
                                v-if="paletteKey === option.value"
                                class="w-4 h-4 text-gray-900 dark:text-white"
                                fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"
                            >
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                            </svg>
                        </div>

                        <!-- Label + Swatches -->
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-700 dark:text-gray-200 truncate">
                                {{ option.label }}
                            </p>
                            <!-- Color swatches -->
                            <div class="flex items-center gap-1 mt-1">
                                <span
                                    v-for="(color, i) in previewColors(option.value)"
                                    :key="i"
                                    class="w-5 h-5 rounded-full ring-1 ring-black/10 dark:ring-white/15 flex-shrink-0"
                                    :style="{ backgroundColor: color }"
                                />
                            </div>
                        </div>
                    </button>
                </div>
            </div>
        </transition>
    </div>
</template>

<script setup>
import { ref, watch, nextTick, onMounted, onBeforeUnmount } from 'vue';
import { useChartPalettes } from '@/Composables/useChartPalettes';

const { paletteKey, paletteOptions, setPalette, previewColors } = useChartPalettes();

const open = ref(false);
const containerRef = ref(null);
const popoverRef = ref(null);
const isMobile = ref(false);
const popoverStyle = ref({});

function selectPalette(key) {
    setPalette(key);
    open.value = false;
}

function onClickOutside(e) {
    if (containerRef.value && !containerRef.value.contains(e.target)) {
        open.value = false;
    }
}

function updatePopoverPosition() {
    if (!open.value) return;

    isMobile.value = window.matchMedia('(max-width: 639px)').matches;
    if (!isMobile.value) {
        popoverStyle.value = {};
        return;
    }

    const anchorRect = containerRef.value?.getBoundingClientRect?.();
    const popoverEl = popoverRef.value;
    if (!anchorRect || !popoverEl) return;

    const viewportW = window.innerWidth || 0;
    const padding = 16; // 1rem
    const width = Math.min(popoverEl.offsetWidth || 288, Math.max(0, viewportW - padding * 2));

    // Align to the trigger's right edge (like desktop), but clamp to viewport.
    let left = anchorRect.right - width;
    left = Math.max(padding, Math.min(left, viewportW - padding - width));

    const top = Math.round(anchorRect.bottom + 8);
    popoverStyle.value = {
        left: `${Math.round(left)}px`,
        top: `${top}px`,
    };
}

watch(open, async (val) => {
    if (!val) return;
    await nextTick();
    updatePopoverPosition();
});

onMounted(() => {
    document.addEventListener('mousedown', onClickOutside);
    window.addEventListener('resize', updatePopoverPosition, { passive: true });
    window.addEventListener('scroll', updatePopoverPosition, true);
});

onBeforeUnmount(() => {
    document.removeEventListener('mousedown', onClickOutside);
    window.removeEventListener('resize', updatePopoverPosition);
    window.removeEventListener('scroll', updatePopoverPosition, true);
});
</script>
