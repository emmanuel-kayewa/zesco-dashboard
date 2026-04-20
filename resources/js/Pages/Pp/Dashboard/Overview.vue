<template>
  <AppLayout
    :directorates="directorates"
    :aiScope="{ type: 'pp_portfolio', directorate_id: directorate.id }"
  >
    <template #title>PP Portfolio Overview</template>
    <!-- ── Sector Quick-Access Cards ── -->
    <div class="mb-2">
      <h3 class="text-lg font-bold text-gray-900 dark:text-white">
        Sectors at a Glance
      </h3>
      <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">
        Click a card to explore that sector
      </p>
    </div>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5 mb-8">
      <Link
        v-for="card in ppData.sectorCards"
        :key="card.sector"
        :href="`/pp/dashboard/explore?sector=${encodeURIComponent(card.sector)}`"
        class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-7 hover:shadow-lg hover:border-[var(--palette-accent-lighter)] dark:hover:border-[var(--palette-accent-dark)] transition-all duration-200 cursor-pointer group"
      >
        <div class="flex items-center justify-between mb-3">
          <div class="flex items-center gap-2 min-w-0">
            <div
              class="w-3 h-3 rounded-full flex-shrink-0"
              :style="{ backgroundColor: card.color }"
            ></div>
            <h4
              class="font-semibold text-gray-900 dark:text-white group-hover:text-zesco-600 dark:group-hover:text-zesco-400 transition-colors truncate"
            >
              {{ card.sector }}
            </h4>
          </div>
          <svg
            class="w-4 h-4 text-gray-400 group-hover:text-zesco-500 transition-colors flex-shrink-0"
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
        </div>

        <div class="grid grid-cols-2 lg:grid-cols-1 2xl:grid-cols-2 gap-4 mb-2">
          <div class="min-w-0">
            <p class="text-xs text-gray-400">Projects</p>
            <p
              class="tabular-nums text-2xl sm:text-3xl lg:text-2xl 2xl:text-3xl font-bold text-gray-900 dark:text-white"
            >
              {{ card.projectCount }}
            </p>
          </div>
          <div class="min-w-0">
            <p class="text-xs text-gray-400">Investment</p>
            <p
              class="tabular-nums text-2xl sm:text-3xl lg:text-2xl 2xl:text-3xl font-bold text-gray-900 dark:text-white"
            >
              ${{ fmtM(card.totalCost) }}
            </p>
          </div>
        </div>

        <div
          class="flex items-center justify-between gap-2 text-xs text-gray-500 dark:text-gray-400 mb-3 lg:flex-col lg:items-start lg:gap-1"
        >
          <span>Paid: ${{ fmtM(card.totalPaid) }}</span>
          <span>Spend Rate: {{ card.spendPct ?? 0 }}%</span>
        </div>

        <div class="flex items-center justify-between">
          <div class="flex items-center gap-1">
            <span v-if="card.totalMw" class="text-xs text-gray-500"
              >{{ card.totalMw }} MW</span
            >
          </div>
          <div class="flex items-center gap-1.5 flex-wrap">
            <span
              v-for="(count, healthStatus) in card.statusCounts || {}"
              :key="healthStatus"
              v-show="count > 0"
              class="flex items-center gap-0.5 text-xs text-gray-500"
            >
              <span
                class="w-2 h-2 rounded-full"
                :style="{
                  backgroundColor:
                    healthStatus === 'On Track'
                      ? '#4ead7a'
                      : healthStatus === 'Delayed'
                        ? '#d4a24e'
                        : '#cf6060',
                }"
              ></span
              >{{ count }} {{ healthStatus }}
            </span>
          </div>
        </div>

        <!-- Progress bar -->
        <div class="mt-3">
          <div
            class="flex items-center justify-between text-xs text-gray-400 mb-1"
          >
            <span>Avg Progress</span>
            <span>{{ card.avgProgress }}%</span>
          </div>
          <div
            class="h-2 bg-gray-200 dark:bg-gray-600 rounded-full overflow-hidden"
          >
            <div
              class="h-full rounded-full transition-all duration-500"
              :style="{
                width: card.avgProgress + '%',
                backgroundColor: card.color,
              }"
            ></div>
          </div>
        </div>
      </Link>
    </div>

    <!-- ── Grid Impact Studies Summary Card ── -->
    <!--
        <div v-if="ppData.gridStudiesSummary && ppData.gridStudiesSummary.totalStudies > 0" class="mb-8">
            <div class="mb-2">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white">Grid Impact Studies</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">IPP grid connection study tracker overview</p>
            </div>
            <Link href="/pp/dashboard/grid-studies"
                  class="block bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 p-5 hover:shadow-lg hover:border-[var(--palette-accent-lighter)] dark:hover:border-[var(--palette-accent-dark)] transition-all duration-200 cursor-pointer group">
                <div class="flex flex-col lg:flex-row lg:items-center gap-6">
                    <div class="grid grid-cols-2 sm:grid-cols-5 gap-4 flex-1">
                        <div>
                            <p class="text-xs text-gray-400">Total Studies</p>
                            <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ ppData.gridStudiesSummary.totalStudies }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-400">Reports</p>
                            <p class="text-2xl font-bold" :style="{ color: CATEGORICAL[0] }">{{ ppData.gridStudiesSummary.totalReports }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-400">Inception</p>
                            <p class="text-2xl font-bold" :style="{ color: CATEGORICAL[5] }">{{ ppData.gridStudiesSummary.totalInception }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-400">Approved</p>
                            <p class="text-2xl font-bold" :style="{ color: RAG.green }">{{ ppData.gridStudiesSummary.approvedCount }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-400">Pipeline MW</p>
                            <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ ppData.gridStudiesSummary.totalCapacityMw }}</p>
                        </div>
                    </div>
                    <div class="flex items-end gap-1 justify-center lg:justify-end flex-shrink-0">
                        <div v-for="stage in ppData.gridStudiesSummary.reportFunnel" :key="stage.stage"
                             class="flex flex-col items-center">
                            <div class="w-8 rounded-t transition-all"
                                 :style="{ height: Math.max(8, (stage.count / Math.max(...ppData.gridStudiesSummary.reportFunnel.map(s => s.count), 1)) * 48) + 'px', backgroundColor: stage.stage === 'Approved' ? RAG.green : stage.stage === 'Under Review' ? RAG.amber : CATEGORICAL[0] }">
                            </div>
                            <p class="text-[8px] text-gray-400 mt-0.5 leading-tight text-center w-8">{{ stage.count }}</p>
                        </div>
                    </div>
                    <div class="hidden lg:flex items-center flex-shrink-0">
                        <svg class="w-5 h-5 text-gray-400 group-hover:text-purple-500 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                    </div>
                </div>
            </Link>
        </div>
        -->
  </AppLayout>
</template>

<script setup>
import { onMounted } from "vue";
import { Link } from "@inertiajs/vue3";
import AppLayout from "@/Components/Layout/AppLayout.vue";
// import { RAG } from '@/Composables/useChartPalette';
import { useDirectorateStore } from "@/stores/useDirectorateStore";

const directorateStore = useDirectorateStore();

const props = defineProps({
  ppData: { type: Object, required: true },
  directorate: { type: Object, required: true },
  directorates: { type: Array, default: () => [] },
});

// Provide summary data to directorate sidebar
onMounted(() => {
  // Auto-enter directorate mode if not already active (e.g. direct URL navigation)
  if (!directorateStore.activeDirectorate) {
    directorateStore.enterDirectorate(props.directorate);
  }
  directorateStore.updateSummary({
    totalProjects: props.ppData.kpis?.totalProjects ?? 0,
    totalCommitted: props.ppData.kpis?.totalCommitted ?? 0,
    spendPct: props.ppData.kpis?.spendPct ?? 0,
  });
});

// ── Helpers ──

function fmtM(val) {
  const n = Number(val) || 0;
  if (n >= 1e9) return (n / 1e9).toFixed(2) + "B";
  if (n >= 1e6) return (n / 1e6).toFixed(1) + "M";
  if (n >= 1e3) return (n / 1e3).toFixed(1) + "K";
  return n.toLocaleString();
}
</script>
