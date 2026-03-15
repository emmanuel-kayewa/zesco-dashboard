<template>
    <div class="card">
        <div class="card-header flex flex-wrap items-center justify-between gap-2">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-white">{{ title }}</h3>
            <slot name="actions" />
        </div>
        <div :class="['card-body', noPadding && '!p-0']">
            <slot />
        </div>
    </div>
</template>

<script setup>
defineProps({
    title: { type: String, required: true },
    noPadding: { type: Boolean, default: false },
});
</script>
