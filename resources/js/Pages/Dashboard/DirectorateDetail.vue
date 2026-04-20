<template>
  <AppLayout :directorates="directorates">
    <template #title>{{ directorate.name }}</template>
    <!-- ══════════════════════════════════════════════════════════
             PP PORTFOLIO DASHBOARD — Redirects to dedicated drill-down pages
             ══════════════════════════════════════════════════════════ -->
    <template v-if="directorate.code === 'PP'">
      <div class="text-center py-16">
        <div
          class="w-16 h-16 mx-auto mb-4 rounded-2xl bg-zesco-100 dark:bg-zesco-900/30 flex items-center justify-center"
        >
          <svg
            class="w-8 h-8 text-zesco-600 dark:text-zesco-400"
            fill="none"
            viewBox="0 0 24 24"
            stroke="currentColor"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"
            />
          </svg>
        </div>
        <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-2">
          PP Portfolio Dashboard
        </h3>
        <p
          class="text-sm text-gray-500 dark:text-gray-400 mb-6 max-w-md mx-auto"
        >
          The Planning &amp; Projects portfolio now has a dedicated drill-down
          dashboard with interactive charts and project explorer.
        </p>
        <Link href="/pp/dashboard" class="btn-primary text-sm px-6 py-2.5">
          Go to PP Dashboard
        </Link>
      </div>
    </template>

    <!-- KPI Cards Row -->
    <!-- <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            <KpiCard
                v-for="kpi in topKpis"
                :key="kpi.id"
                :title="kpi.name"
                :formattedValue="kpi.formatted_value"
                :change="kpi.change"
                :status="kpi.status"
            />
        </div> -->

    <!-- Empty state for directorates with no data -->
    <div
      v-if="directorate.code !== 'PP' && !hasData"
      class="flex flex-col items-center justify-center text-center min-h-[60vh]"
    >
      <div
        class="w-16 h-16 mb-4 rounded-2xl bg-gray-200 dark:bg-gray-800 flex items-center justify-center"
      >
        <svg
          class="w-8 h-8 text-gray-400 dark:text-gray-500"
          fill="none"
          viewBox="0 0 24 24"
          stroke="currentColor"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
          />
        </svg>
      </div>
      <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-2">
        No Data Available
      </h3>
      <p class="text-sm text-gray-500 dark:text-gray-400 max-w-md mx-auto">
        There is no data available for this directorate yet. Data will appear
        here once it has been entered into the system.
      </p>
    </div>

    <!-- KPI Trend & Financial Overview (hidden for PP — uses pp_* tables instead) -->
    <div
      v-if="directorate.code !== 'PP' && hasData"
      class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6 items-start"
    >
      <ChartCard title="KPI Trend" :baseHeight="280">
        <template #default="{ zoomedHeight }">
          <div class="mb-3">
            <Select
              v-model="trendKpiId"
              :options="kpis"
              option-value="id"
              option-label="name"
              size="md"
              @update:modelValue="fetchTrend"
            />
          </div>
          <LineChart
            :data="trendData"
            xField="date"
            yField="value"
            seriesName="Actual"
            :forecast="trendForecast"
            :height="zoomedHeight"
          />
        </template>
      </ChartCard>

      <Card title="Financial Overview">
        <template #actions>
          <div
            v-if="financials.length > perPage"
            class="flex items-center gap-2"
          >
            <span class="text-xs text-gray-400"
              >{{ finPage + 1 }}/{{
                Math.ceil(financials.length / perPage)
              }}</span
            >
            <button
              @click="finPage = Math.max(0, finPage - 1)"
              :disabled="finPage === 0"
              class="p-1 rounded text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 disabled:opacity-30 disabled:cursor-not-allowed"
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
                  d="M15 19l-7-7 7-7"
                />
              </svg>
            </button>
            <button
              @click="
                finPage = Math.min(
                  Math.ceil(financials.length / perPage) - 1,
                  finPage + 1,
                )
              "
              :disabled="finPage >= Math.ceil(financials.length / perPage) - 1"
              class="p-1 rounded text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 disabled:opacity-30 disabled:cursor-not-allowed"
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
                  d="M9 5l7 7-7 7"
                />
              </svg>
            </button>
          </div>
        </template>
        <div v-if="financials.length > 0">
          <div class="space-y-3">
            <div
              v-for="fin in pagedFinancials"
              :key="fin.id"
              class="p-3 bg-gray-50 dark:bg-gray-700/30 rounded-lg"
            >
              <div class="flex items-center justify-between mb-2">
                <span
                  class="text-sm font-medium text-gray-900 dark:text-white"
                  >{{ fin.category }}</span
                >
                <span class="text-xs text-gray-500">{{ fin.period }}</span>
              </div>
              <div class="grid grid-cols-3 gap-2 text-xs">
                <div>
                  <p class="text-gray-400">Budget</p>
                  <p class="font-semibold text-gray-900 dark:text-white">
                    {{ formatCurrency(fin.budget_amount) }}
                  </p>
                </div>
                <div>
                  <p class="text-gray-400">Actual</p>
                  <p class="font-semibold text-gray-900 dark:text-white">
                    {{ formatCurrency(fin.actual_amount) }}
                  </p>
                </div>
                <div>
                  <p class="text-gray-400">Variance</p>
                  <p
                    class="font-semibold"
                    :class="
                      fin.variance >= 0 ? 'text-green-600' : 'text-red-600'
                    "
                  >
                    {{ fin.variance >= 0 ? "+" : ""
                    }}{{ fin.variance?.toFixed(1) }}%
                  </p>
                </div>
              </div>
              <!-- Budget utilization bar -->
              <div
                class="mt-2 h-1.5 bg-gray-200 dark:bg-gray-600 rounded-full overflow-hidden"
              >
                <div
                  class="h-full rounded-full transition-all duration-300"
                  :class="
                    fin.utilization > 100
                      ? 'bg-red-500'
                      : fin.utilization > 85
                        ? 'bg-amber-500'
                        : 'bg-green-500'
                  "
                  :style="{ width: Math.min(fin.utilization || 0, 100) + '%' }"
                ></div>
              </div>
            </div>
          </div>
        </div>
        <div v-else class="text-sm text-gray-400 text-center py-8">
          No financial data available.
        </div>
      </Card>
    </div>

    <!-- Projects & Risks (hidden for PP — uses pp_* tables instead) -->
    <div
      v-if="directorate.code !== 'PP' && hasData"
      class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6 items-start"
    >
      <Card title="Projects">
        <template #actions>
          <div v-if="projects.length > perPage" class="flex items-center gap-2">
            <span class="text-xs text-gray-400"
              >{{ projPage + 1 }}/{{
                Math.ceil(projects.length / perPage)
              }}</span
            >
            <button
              @click="projPage = Math.max(0, projPage - 1)"
              :disabled="projPage === 0"
              class="p-1 rounded text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 disabled:opacity-30 disabled:cursor-not-allowed"
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
                  d="M15 19l-7-7 7-7"
                />
              </svg>
            </button>
            <button
              @click="
                projPage = Math.min(
                  Math.ceil(projects.length / perPage) - 1,
                  projPage + 1,
                )
              "
              :disabled="projPage >= Math.ceil(projects.length / perPage) - 1"
              class="p-1 rounded text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 disabled:opacity-30 disabled:cursor-not-allowed"
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
                  d="M9 5l7 7-7 7"
                />
              </svg>
            </button>
          </div>
        </template>
        <div class="space-y-3">
          <div
            v-for="project in pagedProjects"
            :key="project.id"
            class="p-3 bg-gray-50 dark:bg-gray-700/30 rounded-lg"
          >
            <div class="flex items-center justify-between mb-1">
              <span
                class="text-sm font-medium text-gray-900 dark:text-white truncate"
                >{{ project.name }}</span
              >
              <Badge
                variant="dot"
                :color="getProjectStatusColor(project.status)"
                :label="project.status?.replace('_', ' ')"
              />
            </div>
            <div class="mt-2">
              <div
                class="flex items-center justify-between text-xs text-gray-500 mb-1"
              >
                <span>Progress</span>
                <span>{{ project.completion_percentage }}%</span>
              </div>
              <div
                class="h-1.5 bg-gray-200 dark:bg-gray-600 rounded-full overflow-hidden"
              >
                <div
                  class="h-full bg-zesco-600 rounded-full"
                  :style="{ width: project.completion_percentage + '%' }"
                ></div>
              </div>
            </div>
          </div>
          <p
            v-if="projects.length === 0"
            class="text-sm text-gray-400 text-center py-4"
          >
            No projects tracked.
          </p>
        </div>
      </Card>

      <Card title="Risk Register">
        <template #actions>
          <div v-if="risks.length > perPage" class="flex items-center gap-2">
            <span class="text-xs text-gray-400"
              >{{ riskPage + 1 }}/{{ Math.ceil(risks.length / perPage) }}</span
            >
            <button
              @click="riskPage = Math.max(0, riskPage - 1)"
              :disabled="riskPage === 0"
              class="p-1 rounded text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 disabled:opacity-30 disabled:cursor-not-allowed"
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
                  d="M15 19l-7-7 7-7"
                />
              </svg>
            </button>
            <button
              @click="
                riskPage = Math.min(
                  Math.ceil(risks.length / perPage) - 1,
                  riskPage + 1,
                )
              "
              :disabled="riskPage >= Math.ceil(risks.length / perPage) - 1"
              class="p-1 rounded text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 disabled:opacity-30 disabled:cursor-not-allowed"
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
                  d="M9 5l7 7-7 7"
                />
              </svg>
            </button>
          </div>
        </template>
        <div class="space-y-3">
          <div
            v-for="risk in pagedRisks"
            :key="risk.id"
            class="p-3 bg-gray-50 dark:bg-gray-700/30 rounded-lg"
          >
            <div class="flex items-center justify-between mb-1">
              <span
                class="text-sm font-medium text-gray-900 dark:text-white truncate"
                >{{ risk.title }}</span
              >
              <Badge
                variant="dot"
                :color="getRiskLevelColor(risk.risk_level)"
                :label="risk.risk_level"
              />
            </div>
            <p
              class="text-xs text-gray-500 dark:text-gray-400 mt-1 line-clamp-2"
            >
              {{ risk.description }}
            </p>
            <div class="flex items-center gap-3 mt-2 text-xs text-gray-400">
              <span>Impact: {{ risk.impact }}/5</span>
              <span>Likelihood: {{ risk.likelihood }}/5</span>
              <span
                :class="risk.status === 'mitigated' ? 'text-green-600' : ''"
                >{{ risk.status }}</span
              >
            </div>
          </div>
          <p
            v-if="risks.length === 0"
            class="text-sm text-gray-400 text-center py-4"
          >
            No risks registered.
          </p>
        </div>
      </Card>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, computed, onMounted } from "vue";
import { Link } from "@inertiajs/vue3";
import AppLayout from "@/Components/Layout/AppLayout.vue";
import Card from "@/Components/UI/Card.vue";
import ChartCard from "@/Components/UI/ChartCard.vue";
import Select from "@/Components/UI/Select.vue";
import Badge from "@/Components/UI/Badge.vue";
import LineChart from "@/Components/Charts/LineChart.vue";
import { formatCurrency } from "@/Composables/useFormatters";
import { useBadges } from "@/Composables/useBadges";
import { useDirectorateStore } from "@/stores/useDirectorateStore";

const { getProjectStatusColor, getRiskLevelColor } = useBadges();
const directorateStore = useDirectorateStore();

const props = defineProps({
  directorate: { type: Object, required: true },
  directorates: { type: Array, default: () => [] },
  kpis: { type: Array, default: () => [] },
  kpiSummary: { type: Object, default: () => ({}) },
  financials: { type: Array, default: () => [] },
  projects: { type: Array, default: () => [] },
  risks: { type: Array, default: () => [] },
  trend: { type: Object, default: () => ({ data: [], forecast: [] }) },
  ppPortfolio: { type: Object, default: () => null },
});

const trendKpiId = ref(props.kpis[0]?.id || null);
const trendData = ref(props.trend.data || []);
const trendForecast = ref(props.trend.forecast || []);

const hasData = computed(
  () =>
    props.kpis.length > 0 ||
    props.financials.length > 0 ||
    props.projects.length > 0 ||
    props.risks.length > 0,
);

// Provide summary data to the directorate sidebar
onMounted(() => {
  // Auto-enter directorate mode if not already active (e.g. direct URL navigation)
  if (!directorateStore.activeDirectorate) {
    directorateStore.enterDirectorate(props.directorate);
  }
  const summary =
    props.directorate.code === "PP" && props.ppPortfolio
      ? props.ppPortfolio.boardSummary
      : props.kpiSummary;
  directorateStore.updateSummary(summary);
  if (directorateStore.activeDirectorate) {
    directorateStore.activeDirectorate.kpiCategories = kpiCategories.value;
  }
});

// Pagination for list cards
const perPage = 3;
const finPage = ref(0);
const projPage = ref(0);
const riskPage = ref(0);

const pagedFinancials = computed(() =>
  props.financials.slice(
    finPage.value * perPage,
    (finPage.value + 1) * perPage,
  ),
);
const pagedProjects = computed(() =>
  props.projects.slice(
    projPage.value * perPage,
    (projPage.value + 1) * perPage,
  ),
);
const pagedRisks = computed(() =>
  props.risks.slice(riskPage.value * perPage, (riskPage.value + 1) * perPage),
);

const kpiCategories = computed(() => {
  const cats = new Set(props.kpis.map((k) => k.category).filter(Boolean));
  return Array.from(cats);
});

// const topKpis = computed(() => {
//   let list = props.kpis;
//   if (selectedKpiCategory.value) {
//     list = list.filter((k) => k.category === selectedKpiCategory.value);
//   }
//   return list.slice(0, 8);
// });

async function fetchTrend() {
  if (!trendKpiId.value) return;
  try {
    const params = new URLSearchParams({
      kpi_id: trendKpiId.value,
      directorate_id: props.directorate.id,
    });

    const response = await fetch(`/api/kpi-trend?${params.toString()}`);
    const json = await response.json();
    trendData.value = (json.trend || []).map((p) => ({
      date: p.label ?? p.period,
      value: p.value,
    }));
    trendForecast.value = (json.forecast || []).map((p) => ({
      date: p.label ?? p.period,
      value: p.value,
    }));
  } catch (e) {
    console.error("Failed to fetch trend data:", e);
  }
}
</script>
