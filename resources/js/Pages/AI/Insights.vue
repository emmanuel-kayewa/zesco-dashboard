<template>
  <AppLayout :directorates="directorates">
    <template #title>AI Insights</template>

    <Breadcrumb
      :items="[
        { label: 'Dashboard', href: '/dashboard' },
        { label: 'AI Insights', current: true },
      ]"
    />

    <!-- Mobile: Summary toggle -->
    <div class="lg:hidden mb-4">
      <button
        @click="summaryOpen = !summaryOpen"
        class="w-full flex items-center justify-between rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-4 py-3 text-sm font-medium text-gray-700 dark:text-gray-200 shadow-sm"
      >
        <span class="flex items-center gap-2">
          <SparklesIcon class="w-4 h-4 text-zesco-500" />
          Executive Summary
          <span
            v-if="insights"
            class="h-1.5 w-1.5 rounded-full bg-green-400"
          ></span>
        </span>
        <ChevronDownIcon
          :class="[
            'w-4 h-4 text-gray-400 transition-transform duration-200',
            summaryOpen && 'rotate-180',
          ]"
        />
      </button>

      <Transition
        enter-active-class="transition-all duration-300 ease-out"
        enter-from-class="max-h-0 opacity-0"
        enter-to-class="max-h-[600px] opacity-100"
        leave-active-class="transition-all duration-200 ease-in"
        leave-from-class="max-h-[600px] opacity-100"
        leave-to-class="max-h-0 opacity-0"
      >
        <div
          v-show="summaryOpen"
          class="mt-2 overflow-hidden rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm"
        >
          <div class="p-4 max-h-[500px] overflow-y-auto">
            <div class="flex items-center justify-between mb-4">
              <h3 class="text-sm font-semibold text-gray-900 dark:text-white">
                Executive AI Summary
              </h3>
              <div class="flex items-center gap-2">
                <span
                  v-if="insights?.generated_at"
                  class="text-xs text-gray-400"
                  >{{ formatTime(insights.generated_at) }}</span
                >
                <button
                  @click="loadInsights(true)"
                  :disabled="insightsLoading"
                  class="text-xs text-gray-500 hover:text-gray-700 dark:hover:text-gray-300 flex items-center gap-1"
                >
                  <ArrowPathIcon
                    :class="['w-3.5 h-3.5', insightsLoading && 'animate-spin']"
                  />
                  Refresh
                </button>
              </div>
            </div>
            <InsightsSummaryContent
              :insights="insights"
              :insights-loading="insightsLoading"
              :elapsed-insights="elapsedInsights"
              :ai-available="aiAvailable"
              :error="insightsError"
              @generate="loadInsights()"
            />
          </div>
        </div>
      </Transition>
    </div>

    <!-- Two-column layout: Chat (left) + Summary sidebar (right) -->
    <div class="flex gap-6 items-start">
      <!-- Chat column -->
      <div class="flex-1 min-w-0">
        <Card title="Ask AI">
          <template #actions>
            <button
              @click="clearChat"
              class="text-xs text-gray-400 hover:text-gray-600"
              v-if="chatHistory.length"
            >
              Clear
            </button>
          </template>
          <div
            :class="[
              'space-y-4 flex flex-col lg:min-h-[calc(100vh-232px)]',
              !chatHistory.length && !queryLoading && 'lg:justify-center',
            ]"
          >
            <!-- Chat History -->
            <div
              v-if="chatHistory.length"
              class="max-h-[500px] overflow-y-auto pr-2 flex-1"
            >
              <div class="w-full space-y-8 py-2">
                <div v-for="(msg, idx) in chatHistory" :key="idx">
                  <!-- User message -->
                  <div v-if="msg.role === 'user'" class="flex justify-end">
                    <div class="max-w-[85%] text-right">
                      <div
                        class="inline-flex rounded-full bg-gray-100 dark:bg-gray-700/60 px-4 py-2.5 text-sm font-medium text-gray-700 dark:text-gray-100 shadow-sm"
                      >
                        <p class="whitespace-pre-wrap break-words">
                          {{ msg.content }}
                        </p>
                      </div>
                    </div>
                  </div>

                  <!-- AI message -->
                  <div v-else class="space-y-4">
                    <div
                      class="text-[15px] leading-7 tracking-[-0.01em] text-gray-700 dark:text-gray-200"
                    >
                      <p class="whitespace-pre-wrap break-words">
                        {{ msg.content }}
                      </p>
                    </div>

                    <div
                      v-if="msg.dataPoints && msg.dataPoints.length"
                      class="rounded-2xl border border-zesco-100 bg-zesco-50/70 px-4 py-3 dark:border-zesco-800/40 dark:bg-zesco-900/20"
                    >
                      <p
                        class="text-[11px] font-semibold uppercase tracking-[0.18em] text-zesco-700 dark:text-zesco-300"
                      >
                        Data points
                      </p>
                      <ul
                        class="mt-2 space-y-1.5 pl-4 text-sm text-gray-700 dark:text-gray-200 list-disc"
                      >
                        <li v-for="dp in msg.dataPoints" :key="dp">{{ dp }}</li>
                      </ul>
                    </div>

                    <!-- AI Charts (optional, only when AI includes them) -->
                    <div
                      v-if="msg.charts && msg.charts.length"
                      class="space-y-3"
                    >
                      <AiChart
                        v-for="(chart, ci) in msg.charts"
                        :key="ci"
                        :chart="chart"
                      />
                    </div>

                    <div
                      v-if="msg.suggestions && msg.suggestions.length"
                      class="space-y-2"
                    >
                      <p
                        class="text-[11px] font-semibold uppercase tracking-[0.18em] text-gray-400 dark:text-gray-500"
                      >
                        Try asking
                      </p>
                      <div class="flex flex-wrap gap-2">
                        <button
                          v-for="s in msg.suggestions"
                          :key="s"
                          @click="askQuestion(s)"
                          class="rounded-full border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-700 px-3 py-1.5 text-xs font-medium text-gray-600 dark:text-gray-200 transition hover:border-zesco-300 hover:text-zesco-700 dark:hover:border-zesco-500 dark:hover:text-zesco-300"
                        >
                          {{ s }}
                        </button>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Thinking indicator -->
                <div v-if="queryLoading" class="space-y-3">
                  <div
                    class="flex items-center gap-2 text-sm text-gray-400 dark:text-gray-500"
                  >
                    <div class="flex gap-1">
                      <span
                        class="w-1.5 h-1.5 rounded-full bg-zesco-400 animate-bounce"
                        style="animation-delay: 0ms"
                      />
                      <span
                        class="w-1.5 h-1.5 rounded-full bg-zesco-400 animate-bounce"
                        style="animation-delay: 150ms"
                      />
                      <span
                        class="w-1.5 h-1.5 rounded-full bg-zesco-400 animate-bounce"
                        style="animation-delay: 300ms"
                      />
                    </div>
                    <span class="text-xs font-medium"
                      >Thinking<span v-if="elapsedQuery">
                        ({{ elapsedQuery }}s)</span
                      >…</span
                    >
                  </div>
                </div>
              </div>
            </div>

            <!-- Empty state: Logo + Header + Pills + Input -->
            <div v-if="!chatHistory.length && !queryLoading" class="flex flex-col items-center gap-6 w-full">
              <!-- Zesco Logo -->
              <div>
                <img src="/images/zesco_black_logo.svg" alt="ZESCO" class="h-20 w-auto dark:hidden" />
                <img src="/images/zesco_white_logo.svg" alt="ZESCO" class="h-20 w-auto hidden dark:block" />
              </div>

              <!-- Welcome Header -->
              <div class="text-center">
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white">
                  What would you like to know?
                </h2>
                <p class="mt-1 text-sm text-gray-400 dark:text-gray-500">
                  Ask me anything about KPIs, performance, and trends
                </p>
              </div>

              <!-- Quick Prompts -->
              <div class="w-full">
                <div class="relative flex items-center">
                  <button
                    v-show="canScrollLeft"
                    type="button"
                    @click="scrollPrompts(-200)"
                    class="hidden md:flex flex-shrink-0 p-1 rounded-full text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700/50 transition"
                  >
                    <ChevronLeftIcon class="w-4 h-4" />
                  </button>

                  <div
                    ref="promptsContainer"
                    class="flex gap-2 overflow-x-auto scroll-smooth flex-1 px-1 py-0.5 prompts-scroll"
                    @scroll="updateScrollState"
                  >
                    <button
                      v-for="prompt in quickPrompts"
                      :key="prompt"
                      @click="askQuestion(prompt)"
                      class="flex-shrink-0 rounded-full border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-700 px-3 py-1.5 text-xs font-medium text-gray-600 dark:text-gray-200 transition hover:border-zesco-300 hover:text-zesco-700 dark:hover:border-zesco-500 dark:hover:text-zesco-300 whitespace-nowrap"
                    >
                      {{ prompt }}
                    </button>
                  </div>

                  <button
                    v-show="canScrollRight"
                    type="button"
                    @click="scrollPrompts(200)"
                    class="hidden md:flex flex-shrink-0 p-1 rounded-full text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700/50 transition"
                  >
                    <ChevronRightIcon class="w-4 h-4" />
                  </button>
                </div>
              </div>
            </div>

            <!-- Input -->
            <div :class="['w-full', chatHistory.length && 'mt-auto']">
              <form
                @submit.prevent="submitQuery"
                class="rounded-[28px] border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-800 shadow-sm"
              >
                <div class="px-4 pt-3">
                  <textarea
                    ref="queryInputRef"
                    v-model="queryInput"
                    :disabled="queryLoading || !aiAvailable"
                    rows="1"
                    placeholder="Ask about KPIs, performance, trends..."
                    class="w-full border-0 bg-transparent px-0 py-1 text-sm text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:outline-none focus:ring-0 resize-none overflow-hidden leading-5"
                    @input="resizeQueryInput"
                    @keydown.enter.exact.prevent="submitQuery"
                    @keydown.enter.shift.exact.stop
                  />
                </div>
                <div class="flex items-center justify-end px-3 pb-3 pt-2">
                  <button
                    type="submit"
                    :disabled="
                      queryLoading || !queryInput.trim() || !aiAvailable
                    "
                    class="w-9 h-9 rounded-full flex items-center justify-center transition disabled:opacity-30 disabled:cursor-not-allowed bg-zesco-500 hover:bg-zesco-600 text-white shadow-sm"
                    title="Send"
                  >
                    <svg
                      class="w-4 h-4"
                      fill="none"
                      viewBox="0 0 24 24"
                      stroke="currentColor"
                      stroke-width="2.5"
                    >
                      <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        d="M4.5 10.5L12 3m0 0l7.5 7.5M12 3v18"
                      />
                    </svg>
                  </button>
                </div>
              </form>
              <p
                v-if="showDisclaimer"
                class="w-full px-2 pt-2 text-center text-[11px] leading-4 text-gray-400 dark:text-gray-500"
              >
                AI can make mistakes. Please double-check important information.
              </p>
            </div>
          </div>
        </Card>
      </div>

      <!-- Sidebar: Executive Summary (desktop only) -->
      <div class="hidden lg:block w-[380px] flex-shrink-0 sticky top-6">
        <div
          class="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm"
        >
          <div
            class="flex items-center justify-between px-5 py-4 border-b border-gray-100 dark:border-gray-700"
          >
            <h3
              class="text-sm font-semibold text-gray-900 dark:text-white flex items-center gap-2"
            >
              <SparklesIcon class="w-4 h-4 text-zesco-500" />
              Executive Summary
            </h3>
            <div class="flex items-center gap-2">
              <span
                v-if="insights?.generated_at"
                class="text-[10px] text-gray-400"
                >{{ formatTime(insights.generated_at) }}</span
              >
              <button
                @click="loadInsights(true)"
                :disabled="insightsLoading"
                class="p-1 rounded-md text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700/50 transition"
                title="Refresh insights"
              >
                <ArrowPathIcon
                  :class="['w-3.5 h-3.5', insightsLoading && 'animate-spin']"
                />
              </button>
            </div>
          </div>
          <div
            class="px-5 py-4 min-h-[calc(100vh-200px)] max-h-[calc(100vh-200px)] overflow-y-auto flex flex-col"
          >
            <InsightsSummaryContent
              :insights="insights"
              :insights-loading="insightsLoading"
              :elapsed-insights="elapsedInsights"
              :ai-available="aiAvailable"
              :error="insightsError"
              @generate="loadInsights()"
            />
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { computed, ref, nextTick, onMounted, onUnmounted } from "vue";
import { Link } from "@inertiajs/vue3";
import AppLayout from "@/Components/Layout/AppLayout.vue";
import Breadcrumb from "@/Components/UI/Breadcrumb.vue";
import Card from "@/Components/UI/Card.vue";
import Button from "@/Components/UI/Button.vue";
import InsightsSummaryContent from "@/Components/AI/InsightsSummaryContent.vue";
import AiChart from "@/Components/AI/AiChart.vue";
import {
  ChatBubbleOvalLeftEllipsisIcon,
  SparklesIcon,
  ArrowPathIcon,
} from "@heroicons/vue/24/outline";
import {
  ChevronLeftIcon,
  ChevronRightIcon,
  ChevronDownIcon,
} from "@heroicons/vue/20/solid";

const props = defineProps({
  directorates: { type: Array, default: () => [] },
  aiAvailable: { type: Boolean, default: false },
  aiProvider: { type: String, default: null },
  queueDriver: { type: String, default: "sync" },
});

const csrfToken = () =>
  document.querySelector('meta[name="csrf-token"]')?.content;

// ── UI state ────────────────────────────────────────────
const summaryOpen = ref(false);

// ── Elapsed-time counters ───────────────────────────────
const elapsedQuery = ref(0);
const elapsedInsights = ref(0);
let queryTimer = null;
let insightsTimer = null;

function startTimer(counterRef) {
  counterRef.value = 0;
  return setInterval(() => counterRef.value++, 1000);
}
function stopTimer(timerId, counterRef) {
  clearInterval(timerId);
  counterRef.value = 0;
  return null;
}

// ── Polling helper ──────────────────────────────────────
async function pollTask(taskId, intervalMs = 3000, maxPollMs = 900000) {
  const deadline = Date.now() + maxPollMs;

  while (Date.now() < deadline) {
    await new Promise((r) => setTimeout(r, intervalMs));

    const resp = await fetch(`/api/ai/task/${taskId}`, {
      headers: {
        Accept: "application/json",
        "X-CSRF-TOKEN": csrfToken(),
      },
    });

    if (!resp.ok) {
      const text = await resp.text();
      let message = `Polling failed (HTTP ${resp.status})`;
      try {
        message = JSON.parse(text).message || message;
      } catch {}
      throw new Error(message);
    }

    const data = await resp.json();

    if (data.status === "completed") return data.result;
    if (data.status === "failed")
      throw new Error(data.error || "AI processing failed");
    // else: queued / running — keep polling
  }

  throw new Error("AI request timed out");
}

/**
 * Post to an AI endpoint. Handles both sync and async (job) responses.
 * If the response contains { async: true, task_id }, it polls until completion.
 */
async function aiPost(url, body = {}) {
  const resp = await fetch(url, {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
      Accept: "application/json",
      "X-CSRF-TOKEN": csrfToken(),
    },
    body: JSON.stringify(body),
  });

  // Guard: read as text first so an HTML error page doesn't blow up JSON.parse
  const text = await resp.text();

  let data;
  try {
    data = JSON.parse(text);
  } catch {
    console.error("Non-JSON response from", url, ":", text.substring(0, 300));
    throw new Error(
      resp.status === 419
        ? "Session expired — please refresh the page."
        : `Server returned an unexpected response (HTTP ${resp.status}). Please try again.`,
    );
  }

  if (!resp.ok) {
    throw new Error(data.message || `Request failed (HTTP ${resp.status})`);
  }

  // Async job — poll for the result
  if (data.async && data.task_id) {
    return await pollTask(data.task_id);
  }

  // Sync result returned directly
  return data;
}

// ── Chat ────────────────────────────────────────────────
const queryInput = ref("");
const queryInputRef = ref(null);
const promptsContainer = ref(null);
const queryLoading = ref(false);
const chatHistory = ref([]);
const showDisclaimer = computed(
  () => chatHistory.value.length > 0 || queryLoading.value,
);

function resizeQueryInput() {
  const el = queryInputRef.value;
  if (!el) return;
  el.style.height = "auto";
  el.style.height = Math.min(el.scrollHeight, 120) + "px";
}

const canScrollLeft = ref(false);
const canScrollRight = ref(true);

function updateScrollState() {
  const el = promptsContainer.value;
  if (!el) return;
  canScrollLeft.value = el.scrollLeft > 0;
  canScrollRight.value = el.scrollLeft + el.clientWidth < el.scrollWidth - 1;
}

function scrollPrompts(offset) {
  promptsContainer.value?.scrollBy({ left: offset, behavior: "smooth" });
}

const quickPrompts = [
  "Summarize this quarter's performance",
  "Which directorates are underperforming?",
  "What KPIs are at risk of missing targets?",
  "Compare directorate performance",
  "What are the top 3 risks right now?",
  "Suggest improvement areas",
];

function askQuestion(question) {
  queryInput.value = question;
  submitQuery();
}

async function submitQuery() {
  const question = queryInput.value.trim();
  if (!question) return;

  chatHistory.value.push({ role: "user", content: question });
  queryInput.value = "";
  queryLoading.value = true;
  queryTimer = startTimer(elapsedQuery);

  try {
    const data = await aiPost("/api/ai/query", { question });

    // Async result comes as the raw AI output; sync result is wrapped
    const result = data.result || data;

    if (result && (result.answer || data.success)) {
      chatHistory.value.push({
        role: "assistant",
        content: result.answer || "I couldn't generate an answer.",
        dataPoints: result.data_points || [],
        suggestions: result.follow_up_suggestions || [],
        charts: result.charts || [],
      });
    } else {
      chatHistory.value.push({
        role: "assistant",
        content: data.message || "Sorry, I couldn't process that request.",
      });
    }
  } catch (e) {
    chatHistory.value.push({
      role: "assistant",
      content: e.message || "Failed to connect to AI service.",
    });
  } finally {
    queryTimer = stopTimer(queryTimer, elapsedQuery);
    queryLoading.value = false;
  }
}

function clearChat() {
  chatHistory.value = [];
}

// ── Executive Insights ──────────────────────────────────
const insights = ref(null);
const insightsLoading = ref(false);
const insightsError = ref("");

async function loadInsights(fresh = false) {
  insightsLoading.value = true;
  insightsError.value = "";
  insightsTimer = startTimer(elapsedInsights);

  try {
    const data = await aiPost("/api/ai/executive-insights", { fresh });

    // Sync response: { success, insights: {...} }. Async response: raw AI object.
    const parsed = data.insights ?? data;
    if (
      parsed &&
      typeof parsed === "object" &&
      Object.keys(parsed).length > 0
    ) {
      insights.value = parsed;
    }
  } catch (e) {
    insights.value = null;
    insightsError.value = e?.message || "Failed to load insights.";
    console.error("Failed to load insights", e);
  } finally {
    insightsTimer = stopTimer(insightsTimer, elapsedInsights);
    insightsLoading.value = false;
  }
}

function formatTime(iso) {
  if (!iso) return "";
  const d = new Date(iso);
  return d.toLocaleString("en-ZM", { dateStyle: "medium", timeStyle: "short" });
}

onMounted(() => {
  if (props.aiAvailable) {
    loadInsights();
  }
  nextTick(() => updateScrollState());
});

onUnmounted(() => {
  if (queryTimer) clearInterval(queryTimer);
  if (insightsTimer) clearInterval(insightsTimer);
});
</script>

<style scoped>
.prompts-scroll {
  -ms-overflow-style: none;
  scrollbar-width: none;
}
.prompts-scroll::-webkit-scrollbar {
  display: none;
}
</style>
