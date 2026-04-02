<template>
  <div>
    <label
      v-if="label"
      :for="id"
      class="mb-1.5 block text-sm font-medium text-zinc-700 dark:text-gray-300"
    >
      {{ label }}
      <span v-if="required" class="text-red-600">*</span>
    </label>
    <div class="relative">
      <div
        class="pointer-events-none absolute inset-y-0 start-0 flex items-center ps-3"
      >
        <svg
          class="size-4 text-gray-400 dark:text-gray-500"
          aria-hidden="true"
          xmlns="http://www.w3.org/2000/svg"
          width="24"
          height="24"
          fill="none"
          viewBox="0 0 24 24"
        >
          <path
            stroke="currentColor"
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M4 10h16m-8-3V4M7 7V4m10 3V4M5 20h14a1 1 0 0 0 1-1V7a1 1 0 0 0-1-1H5a1 1 0 0 0-1 1v12a1 1 0 0 0 1 1Zm3-7h.01v.01H8V13Zm4 0h.01v.01H12V13Zm4 0h.01v.01H16V13Z"
          />
        </svg>
      </div>
      <input
        ref="inputRef"
        :id="id"
        type="text"
        :placeholder="placeholder"
        :required="required"
        :disabled="disabled"
        :class="inputClasses"
      />
    </div>
    <p v-if="error" class="mt-1 text-xs text-red-600 dark:text-red-400">
      {{ error }}
    </p>
    <p
      v-else-if="helpText"
      class="mt-1 text-xs text-gray-500 dark:text-gray-400"
    >
      {{ helpText }}
    </p>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount, watch } from "vue";
import { Datepicker } from "flowbite";

const props = defineProps({
  modelValue: {
    type: [String, null],
    default: null,
  },
  label: {
    type: String,
    default: "",
  },
  placeholder: {
    type: String,
    default: "Select date",
  },
  format: {
    type: String,
    default: "yyyy-mm-dd",
  },
  autohide: {
    type: Boolean,
    default: true,
  },
  minDate: {
    type: [String, null],
    default: null,
  },
  maxDate: {
    type: [String, null],
    default: null,
  },
  required: {
    type: Boolean,
    default: false,
  },
  disabled: {
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
  id: {
    type: String,
    default: () => `datepicker-${Math.random().toString(36).substr(2, 9)}`,
  },
});

const emit = defineEmits(["update:modelValue"]);

const inputRef = ref(null);
let datepickerInstance = null;

const inputClasses = computed(() => {
  let classes =
    "block w-full rounded-lg border border-zinc-200 border-b-zinc-300/80 bg-white py-3 ps-10 pe-4 text-sm text-zinc-700 shadow-sm transition-colors duration-200 placeholder:text-gray-400 focus:border-zinc-900 focus:outline-none focus:ring-2 focus:ring-zinc-900 focus:ring-offset-2 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 dark:placeholder:text-gray-500 dark:focus:border-white dark:focus:ring-white dark:focus:ring-offset-gray-900";

  if (props.error) {
    classes += " ring-red-500 dark:ring-red-500 text-red-900 dark:text-red-400";
  }

  if (props.disabled) {
    classes += " opacity-50 cursor-not-allowed";
  }

  return classes;
});

function handleChangeDate() {
  if (!inputRef.value) return;
  const value = inputRef.value.value;
  if (value !== props.modelValue) {
    emit("update:modelValue", value);
  }
}

onMounted(() => {
  if (!inputRef.value) return;

  const options = {
    autohide: props.autohide,
    format: props.format,
    orientation: "auto",
  };

  if (props.minDate) {
    options.minDate = props.minDate;
  }
  if (props.maxDate) {
    options.maxDate = props.maxDate;
  }

  datepickerInstance = new Datepicker(inputRef.value, options);

  if (props.modelValue) {
    datepickerInstance.setDate(props.modelValue);
  }

  inputRef.value.addEventListener("changeDate", handleChangeDate);
});

onBeforeUnmount(() => {
  if (inputRef.value) {
    inputRef.value.removeEventListener("changeDate", handleChangeDate);
  }
  if (datepickerInstance) {
    datepickerInstance.destroy();
    datepickerInstance = null;
  }
});

watch(
  () => props.modelValue,
  (newValue) => {
    if (!datepickerInstance || !inputRef.value) return;

    if (newValue !== inputRef.value.value) {
      if (newValue) {
        datepickerInstance.setDate(newValue);
      } else {
        datepickerInstance.setDate({ clear: true });
        inputRef.value.value = "";
      }
    }
  },
);
</script>

<style>
/* 
Ensure the datepicker popup renders above and stays readable.
Flowbite's JS datepicker uses fixed classes that need specific z-index in modals.
*/
.datepicker {
  z-index: 100 !important;
}

/* Fix rounding, colors, and hover states to match Flowbite standard */
.datepicker-picker {
  border-radius: 0.5rem !important; /* rounded-lg equivalent */
  background-color: white !important;
  border: 1px solid #e5e7eb !important; /* gray-200 */
  box-shadow:
    0 10px 15px -3px rgba(0, 0, 0, 0.1),
    0 4px 6px -2px rgba(0, 0, 0, 0.05) !important;
  padding: 1rem !important;
}

.dark .datepicker-picker {
  background-color: #1f2937 !important; /* gray-800 */
  border-color: #374151 !important; /* gray-700 */
}

/* Date cell styling */
.datepicker-cell {
  border-radius: 0.5rem !important;
  font-weight: 500 !important;
}

/* Hover state */
.datepicker-cell:hover {
  background-color: #f3f4f6 !important; /* gray-100 */
  cursor: pointer !important;
}

.dark .datepicker-cell:hover {
  background-color: #374151 !important; /* gray-700 */
}

/* Selected state */
.datepicker-cell.selected {
  background-color: black !important;
  color: white !important;
}

.dark .datepicker-cell.selected {
  background-color: white !important;
  color: #1f2937 !important; /* gray-800 */
}

/* Distinguished months (previous/next month dates) */
.datepicker-cell.prev:not(.disabled),
.datepicker-cell.next:not(.disabled) {
  color: #9ca3af !important; /* gray-400 */
}

.dark .datepicker-cell.prev:not(.disabled),
.dark .datepicker-cell.next:not(.disabled) {
  color: #4b5563 !important; /* gray-600 */
}

/* Controls (Prev/Next/View switch) */
.datepicker-controls .button,
.datepicker-controls button {
  border-radius: 0.5rem !important;
  background-color: transparent !important;
  color: inherit !important;
  cursor: pointer !important;
  transition: background-color 150ms ease-in-out !important;
}

.datepicker-controls .button:hover,
.datepicker-controls button:hover {
  background-color: #f3f4f6 !important;
}

.dark .datepicker-controls .button:hover,
.dark .datepicker-controls button:hover {
  background-color: #374151 !important;
}

/* Input Styles Fix: ensure slightly rounded corners (rounded-lg) */
.datepicker-input {
  border-radius: 0.5rem !important;
}
</style>
