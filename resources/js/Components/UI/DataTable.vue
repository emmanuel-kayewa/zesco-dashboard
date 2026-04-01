<template>
  <div class="overflow-x-auto">
    <table class="w-full text-sm" :class="tableClass">
      <thead>
        <tr class="border-b border-gray-200 dark:border-gray-700">
          <th
            v-for="col in columns"
            :key="col.key"
            class="py-2 px-3 text-xs font-semibold text-gray-500 uppercase"
            :class="[alignClass(col.align), col.class]"
          >
            {{ col.label }}
          </th>
        </tr>
      </thead>
      <tbody>
        <tr
          v-for="(row, index) in rows"
          :key="row.id ?? index"
          class="border-b border-gray-100 dark:border-gray-700/50 hover:bg-gray-50 dark:hover:bg-gray-700/20"
        >
          <slot :row="row" :index="index" />
        </tr>
        <tr v-if="!rows?.length">
          <td
            :colspan="columns.length"
            class="text-center py-8 text-gray-400 text-sm"
          >
            {{ emptyMessage }}
          </td>
        </tr>
        <slot name="summary" />
      </tbody>
    </table>
  </div>

  <!-- Pagination -->
  <div
    v-if="pagination?.length > 3"
    class="flex items-center justify-center gap-1 mt-4 pt-4 border-t border-gray-100 dark:border-gray-700"
  >
    <template v-for="link in pagination" :key="link.label">
      <Link
        v-if="link.url"
        :href="link.url"
        class="px-3 py-1 rounded text-xs"
        :class="
          link.active
            ? 'bg-zesco-600 text-white'
            : 'hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-600 dark:text-gray-400'
        "
        v-html="link.label"
      />
      <span
        v-else
        class="px-3 py-1 text-xs text-gray-400"
        v-html="link.label"
      />
    </template>
  </div>
</template>

<script setup>
import { Link } from "@inertiajs/vue3";

defineProps({
  columns: {
    type: Array,
    required: true,
  },
  rows: {
    type: Array,
    default: () => [],
  },
  pagination: {
    type: Array,
    default: () => [],
  },
  emptyMessage: {
    type: String,
    default: "No records found.",
  },
  tableClass: {
    type: String,
    default: "",
  },
});

function alignClass(align) {
  if (align === "right") return "text-right";
  if (align === "center") return "text-center";
  return "text-left";
}
</script>
