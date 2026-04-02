<template>
  <div
    :class="[
      'flex border-b border-gray-200 dark:border-gray-700 gap-1 overflow-x-auto pb-1',
      containerClass,
    ]"
    :aria-label="ariaLabel"
  >
    <component
      v-for="tab in tabs"
      :key="tab.key"
      :is="mode === 'link' ? Link : 'button'"
      :href="mode === 'link' ? tab.href : undefined"
      :type="mode === 'button' ? 'button' : undefined"
      class="whitespace-nowrap flex-shrink-0 transition-colors"
      :class="[tabBaseClasses, isActive(tab) ? activeClasses : inactiveClasses]"
      :aria-current="isActive(tab) ? 'page' : undefined"
      :title="tab.label"
      @click="onTabClick(tab)"
    >
      <span>{{ tab.label }}</span>
      <span
        v-if="tab.count !== undefined && tab.count !== null"
        class="ml-1.5 text-xs bg-gray-100 dark:bg-gray-700 px-1.5 py-0.5 rounded-full"
      >
        {{ tab.count }}
      </span>
    </component>
  </div>
</template>

<script setup>
import { computed } from "vue";
import { Link } from "@inertiajs/vue3";

const props = defineProps({
  modelValue: { type: String, default: "" },
  tabs: {
    type: Array,
    required: true,
    // [{ key: string, label: string, href?: string, count?: number }]
  },
  mode: {
    type: String,
    default: "button",
    validator: (v) => ["button", "link"].includes(v),
  },
  size: {
    type: String,
    default: "sm",
    validator: (v) => ["sm", "md"].includes(v),
  },
  activeClass: { type: String, default: "" },
  inactiveClass: { type: String, default: "" },
  ariaLabel: { type: String, default: "Tabs" },
  containerClass: { type: String, default: "" },
});

const emit = defineEmits(["update:modelValue", "tab-change"]);

const tabBaseClasses = computed(() => {
  if (props.size === "md") {
    return "px-4 py-2.5 text-sm font-medium rounded-md border-b-2";
  }
  return "px-3 py-2 text-xs font-medium rounded-md border-b-2";
});

const defaultActiveClasses =
  "border-transparent text-gray-900 dark:text-white bg-gray-100 dark:bg-gray-700/40";
const defaultInactiveClasses =
  "border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200";

const activeClasses = computed(() => props.activeClass || defaultActiveClasses);
const inactiveClasses = computed(
  () => props.inactiveClass || defaultInactiveClasses,
);

function isActive(tab) {
  return props.modelValue === tab.key;
}

function onTabClick(tab) {
  emit("tab-change", tab.key);
  if (props.mode === "button") {
    emit("update:modelValue", tab.key);
  }
}
</script>
