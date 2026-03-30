<template>
  <AppLayout>
    <template #title>PP Weekly Reports</template>

    <div class="space-y-6">
      <!-- Header (hidden when no reports) -->
      <div
        v-if="reports.data?.length"
        class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4"
      >
        <div>
          <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
            Weekly Brief Reports
          </h1>
          <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
            ZESCO, IPP Solar PV and Transmission Projects
          </p>
        </div>
        <Link
          v-if="canCreate && isPpRoute"
          href="/pp/weekly-reports/create"
          class="self-start inline-flex items-center gap-2 px-4 py-2.5 rounded-lg bg-gray-900 dark:bg-white text-white dark:text-gray-900 text-sm font-medium hover:bg-gray-800 dark:hover:bg-gray-100 transition shadow-sm"
        >
          <svg
            class="w-4 h-4"
            fill="none"
            viewBox="0 0 24 24"
            stroke="currentColor"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M12 4v16m8-8H4"
            />
          </svg>
          New Report
        </Link>
      </div>

      <!-- Report Cards Grid -->
      <div
        v-if="reports.data?.length"
        class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5"
      >
        <WeeklyReportCard
          v-for="report in reports.data"
          :key="report.id"
          :report="report"
          :href="viewUrl(report)"
          :showActions="canCreate && isPpRoute"
          @edit="goToEdit"
          @delete="deleteReport"
        />
      </div>

      <!-- Empty State -->
      <div v-else class="text-center py-24">
        <div
          class="inline-flex items-center justify-center w-20 h-20 rounded-3xl bg-gray-100 dark:bg-gray-700/60 mb-5"
        >
          <svg
            class="w-20 h-20 text-gray-400 dark:text-gray-500"
            fill="none"
            viewBox="0 0 24 24"
            stroke="currentColor"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="1.5"
              d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
            />
          </svg>
        </div>
        <h3 class="text-base font-semibold text-gray-900 dark:text-white mb-2">
          No weekly reports yet
        </h3>
        <p
          v-if="canCreate && isPpRoute"
          class="text-sm text-gray-500 dark:text-gray-400 mb-6"
        >
          Get started by creating the first weekly brief report.
        </p>
        <Button
          v-if="canCreate && isPpRoute"
          size="md"
          @click="router.visit('/pp/weekly-reports/create')"
        >
          <svg
            class="w-4 h-4"
            fill="none"
            viewBox="0 0 24 24"
            stroke="currentColor"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M12 4v16m8-8H4"
            />
          </svg>
          Create Report
        </Button>
      </div>

      <!-- Pagination -->
      <div
        v-if="reports.links?.length > 3"
        class="flex items-center justify-center gap-1 pt-2"
      >
        <template v-for="link in reports.links" :key="link.label">
          <Link
            v-if="link.url"
            :href="link.url"
            class="px-3 py-1.5 rounded-lg text-xs font-medium transition"
            :class="
              link.active
                ? 'bg-zesco-600 text-white shadow-sm'
                : 'hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-600 dark:text-gray-400'
            "
            v-html="link.label"
          />
          <span
            v-else
            class="px-3 py-1.5 text-xs text-gray-400"
            v-html="link.label"
          />
        </template>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { computed } from "vue";
import { Link, router, usePage } from "@inertiajs/vue3";
import AppLayout from "@/Components/Layout/AppLayout.vue";
import WeeklyReportCard from "@/Components/Pp/WeeklyReportCard.vue";
import Button from "@/Components/UI/Button.vue";

const props = defineProps({
  reports: { type: Object, default: () => ({ data: [], links: [] }) },
});

const page = usePage();
const auth = computed(() => page.props.auth?.user);
const isPpRoute = computed(() => page.url.startsWith("/pp/"));

const canCreate = computed(() => !!auth.value);

function viewUrl(report) {
  if (isPpRoute.value) {
    return `/pp/weekly-reports/${report.id}`;
  }
  return `/weekly-reports/${report.id}`;
}

function goToEdit(report) {
  router.visit(`/pp/weekly-reports/${report.id}/edit`);
}

function deleteReport(id) {
  if (confirm("Delete this weekly report? This action cannot be undone.")) {
    router.delete(`/pp/weekly-reports/${id}`, { preserveScroll: true });
  }
}
</script>
