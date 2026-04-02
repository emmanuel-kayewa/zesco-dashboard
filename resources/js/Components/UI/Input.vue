<template>
  <div class="">
    <label
      v-if="label"
      :for="id"
      :title="label"
      class="block mb-1.5 text-sm font-medium text-zinc-700 dark:text-gray-300"
      :class="labelClass"
    >
      {{ label }}
      <span v-if="required" class="text-red-600">*</span>
    </label>
    <div class="relative">
      <div
        v-if="icon"
        class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none"
      >
        <svg
          class="w-4 h-4 text-gray-500 dark:text-gray-400"
          aria-hidden="true"
          xmlns="http://www.w3.org/2000/svg"
          fill="none"
          viewBox="0 0 24 24"
          stroke="currentColor"
        >
          <path
            v-if="icon === 'search'"
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
          />
          <path
            v-else-if="icon === 'email'"
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"
          />
          <path
            v-else-if="icon === 'calendar'"
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"
          />
        </svg>
      </div>
      <input
        :id="id"
        :type="type"
        :value="modelValue"
        @input="$emit('update:modelValue', $event.target.value)"
        @blur="$emit('blur', $event)"
        @focus="$emit('focus', $event)"
        :placeholder="placeholder"
        :required="required"
        :disabled="disabled"
        :readonly="readonly"
        :min="min"
        :max="max"
        :step="step"
        :class="inputClasses"
      />
      <div
        v-if="helpText && !error"
        class="mt-2 text-sm text-gray-500 dark:text-gray-400"
      >
        {{ helpText }}
      </div>
      <div v-if="error" class="mt-2 text-sm text-red-600 dark:text-red-500">
        {{ error }}
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from "vue";

const props = defineProps({
  modelValue: {
    type: [String, Number],
    default: "",
  },
  type: {
    type: String,
    default: "text",
  },
  label: {
    type: String,
    default: "",
  },
  labelClass: {
    type: String,
    default: "",
  },
  placeholder: {
    type: String,
    default: "",
  },
  required: {
    type: Boolean,
    default: false,
  },
  disabled: {
    type: Boolean,
    default: false,
  },
  readonly: {
    type: Boolean,
    default: false,
  },
  error: {
    type: String,
    default: "",
  },
  helpText: {
    type: String,
    default: "",
  },
  size: {
    type: String,
    default: "md", // sm, md, lg
    validator: (value) => ["sm", "md", "lg"].includes(value),
  },
  icon: {
    type: String,
    default: "",
  },
  id: {
    type: String,
    default: () => `input-${Math.random().toString(36).substr(2, 9)}`,
  },
  min: {
    type: [String, Number],
    default: undefined,
  },
  max: {
    type: [String, Number],
    default: undefined,
  },
  step: {
    type: [String, Number],
    default: undefined,
  },
});

defineEmits(["update:modelValue", "blur", "focus"]);

const inputClasses = computed(() => {
  const baseClasses =
    "block w-full border rounded-lg shadow-sm transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 dark:focus:ring-offset-gray-900";

  // Size classes - using explicit heights to match Select
  const sizeClasses = {
    sm: "h-8 px-3 py-1 text-xs",
    md: "h-10 px-3 py-2 text-sm",
    lg: "h-12 px-4 py-3 text-base",
  };

  // Icon padding
  const iconPadding = props.icon ? "pl-10" : "";

  // Focus Ring and Border (matching Select component)
  const focusClasses =
    "focus:ring-zinc-900 focus:border-zinc-900 dark:focus:ring-white dark:focus:border-white";

  // State-based classes (error, disabled, or normal)
  let stateClasses = "";
  if (props.error) {
    stateClasses =
      "bg-red-50 border-red-500 text-red-900 placeholder-red-700 dark:bg-gray-800 dark:text-red-400 dark:border-red-500";
  } else if (props.disabled) {
    stateClasses =
      "bg-gray-50 border-zinc-200 text-zinc-700 opacity-50 cursor-not-allowed dark:bg-gray-700 dark:border-gray-600 dark:text-gray-400";
  } else {
    stateClasses =
      "bg-white border-zinc-200 border-b-zinc-300/80 text-zinc-700 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-200 dark:placeholder-gray-500";
  }

  return [
    baseClasses,
    sizeClasses[props.size],
    iconPadding,
    focusClasses,
    stateClasses,
  ].join(" ");
});
</script>
