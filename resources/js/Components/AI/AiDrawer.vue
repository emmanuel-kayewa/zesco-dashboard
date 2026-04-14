<template>
  <div>
    <!-- Backdrop (mobile only) -->
    <Transition
      enter-active-class="transition-opacity duration-300"
      enter-from-class="opacity-0"
      enter-to-class="opacity-100"
      leave-active-class="transition-opacity duration-200"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div
        v-if="open"
        class="fixed inset-0 z-[59] bg-black/40 no-print lg:hidden"
        @click="$emit('close')"
      />
    </Transition>

    <!-- Panel -->
    <aside :class="panelClasses">
      <!-- Top bar -->
      <div class="flex items-center justify-between px-4 py-3 flex-shrink-0">
        <div class="w-8" />
        <div class="flex items-center gap-2">
          <!-- Expand / Collapse (hidden on mobile) -->
          <button
            type="button"
            class="hidden lg:flex p-1.5 rounded-lg text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700/50 transition"
            :title="expanded ? 'Collapse' : 'Expand'"
            @click="toggleExpand"
          >
            <!-- Expand icon -->
            <ArrowsPointingOutIcon v-if="!expanded" class="w-4 h-4" />
            <!-- Collapse icon -->
            <ArrowsPointingInIcon v-else class="w-4 h-4" />
          </button>

          <!-- Close -->
          <button
            type="button"
            class="p-1.5 rounded-lg text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700/50 transition"
            title="Close"
            @click="$emit('close')"
          >
            <XMarkIcon class="w-4 h-4" />
          </button>
        </div>
      </div>

      <!-- Body -->
      <div class="flex-1 flex flex-col overflow-hidden">
        <!-- Empty state (no messages yet) -->
        <div
          v-if="chat.length === 0 && !statusError"
          class="flex-1 flex flex-col items-center justify-center px-6 gap-6"
        >
          <!-- ZESCO Logo -->
          <div class="relative">
            <div class="w-26 h-26 flex items-center justify-center">
              <img
                src="/images/zesco_black_logo.svg"
                alt="ZESCO"
                class="w-24 h-24 object-contain dark:hidden"
              />
              <img
                src="/images/zesco_white_logo.svg"
                alt="ZESCO"
                class="w-24 h-24 object-contain hidden dark:block"
              />
            </div>
          </div>

          <div class="text-center">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
              AI Assistant
            </h2>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
              {{ scopeLabel }}
              <!-- <span v-if="provider" class="ml-1">· {{ provider }}</span> -->
            </p>
          </div>

          <!-- Input -->
          <div class="w-full max-w-md">
            <form @submit.prevent="submit">
              <div class="relative">
                <textarea
                  ref="inputRef"
                  v-model="input"
                  :disabled="loading"
                  rows="1"
                  placeholder="Ask a question..."
                  class="w-full pl-4 pr-24 py-3 rounded-xl border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700/50 text-sm text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-zesco-400/50 dark:focus:ring-zesco-500/50 focus:border-zesco-400 dark:focus:border-zesco-500 transition resize-none overflow-hidden leading-5"
                  @input="resizeInput"
                  @keydown.enter.exact.prevent="submit"
                  @keydown.enter.shift.exact.stop
                />
                <div
                  class="absolute right-2 top-1/2 -translate-y-1/2 flex items-center gap-1"
                >
                  <Link
                    href="/ai"
                    class="p-1.5 rounded-lg text-gray-400 hover:text-zesco-600 dark:hover:text-zesco-400 transition"
                    title="Open AI Insights"
                  >
                    <ArrowTopRightOnSquareIcon class="w-4 h-4" />
                  </Link>
                  <button
                    type="submit"
                    :disabled="loading || !input.trim()"
                    class="w-8 h-8 rounded-full flex items-center justify-center transition disabled:opacity-30 disabled:cursor-not-allowed bg-zesco-500 hover:bg-zesco-600 text-white"
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
              </div>
            </form>
          </div>

          <!-- Suggested questions -->
          <div
            class="w-full max-w-md space-y-0 divide-y divide-gray-100 dark:divide-gray-700/50"
          >
            <button
              v-for="(q, i) in defaultSuggestions"
              :key="i"
              type="button"
              class="w-full flex items-center gap-3 px-3 py-3 text-left text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700/30 transition rounded-lg"
              @click="askSuggestion(q)"
            >
              <ChatBubbleOvalLeftEllipsisIcon
                class="w-4 h-4 flex-shrink-0 text-gray-400 dark:text-gray-500"
              />
              <span>{{ q }}</span>
            </button>
          </div>
        </div>

        <!-- Error banner -->
        <div v-if="statusError" class="mx-4 mt-2">
          <div
            class="text-sm text-red-600 dark:text-red-400 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 px-3 py-2 rounded-lg"
          >
            {{ statusError }}
          </div>
        </div>

        <!-- Chat messages -->
        <div
          v-if="chat.length > 0"
          ref="chatContainer"
          class="flex-1 overflow-y-auto px-4 py-6 sm:px-6"
        >
          <div class="mx-auto w-full max-w-3xl space-y-8">
            <div v-for="(m, idx) in chat" :key="idx">
              <div v-if="m.role === 'user'" class="flex justify-end">
                <div class="max-w-[85%] text-right space-y-2">
                  <div v-if="idx === 0" class="flex justify-end">
                    <span
                      class="inline-flex items-center gap-1 rounded-md border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-700 px-2.5 py-1 text-xs font-medium text-gray-600 dark:text-gray-300 shadow-sm"
                    >
                      <span class="h-2 w-2 rounded-sm bg-zesco-500"></span>
                      {{ scopeLabel }}
                    </span>
                  </div>
                  <div
                    class="inline-flex rounded-full bg-gray-100 dark:bg-gray-700/60 px-4 py-2.5 text-sm font-medium text-gray-700 dark:text-gray-100 shadow-sm"
                  >
                    <p class="whitespace-pre-wrap break-words">
                      {{ m.content }}
                    </p>
                  </div>
                </div>
              </div>

              <div v-else class="space-y-4">
                <div
                  class="max-w-3xl text-[15px] leading-7 tracking-[-0.01em] text-gray-700 dark:text-gray-200"
                >
                  <p class="whitespace-pre-wrap break-words">{{ m.content }}</p>
                </div>

                <div
                  v-if="m.dataPoints && m.dataPoints.length"
                  class="max-w-3xl rounded-2xl border border-zesco-100 bg-zesco-50/70 px-4 py-3 dark:border-zesco-800/40 dark:bg-zesco-900/20"
                >
                  <p
                    class="text-[11px] font-semibold uppercase tracking-[0.18em] text-zesco-700 dark:text-zesco-300"
                  >
                    Data points
                  </p>
                  <ul
                    class="mt-2 space-y-1.5 pl-4 text-sm text-gray-700 dark:text-gray-200 list-disc"
                  >
                    <li v-for="(dp, i) in m.dataPoints" :key="i">{{ dp }}</li>
                  </ul>
                </div>

                <div
                  v-if="m.suggestions && m.suggestions.length"
                  class="max-w-3xl space-y-2"
                >
                  <p
                    class="text-[11px] font-semibold uppercase tracking-[0.18em] text-gray-400 dark:text-gray-500"
                  >
                    Try asking
                  </p>
                  <div class="flex flex-wrap gap-2">
                    <button
                      v-for="(s, i) in m.suggestions"
                      :key="i"
                      type="button"
                      class="rounded-full border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-700 px-3 py-1.5 text-xs font-medium text-gray-600 dark:text-gray-200 transition hover:border-zesco-300 hover:text-zesco-700 dark:hover:border-zesco-500 dark:hover:text-zesco-300"
                      @click="askSuggestion(s)"
                    >
                      {{ s }}
                    </button>
                  </div>
                </div>
              </div>
            </div>

            <!-- Thinking indicator -->
            <div v-if="loading" class="max-w-3xl space-y-3">
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
                <span class="text-xs font-medium">Thinking…</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Input bar (shown when chat has messages) -->
        <div
          v-if="chat.length > 0"
          class="border-t border-gray-200/80 dark:border-gray-700 bg-white/90 dark:bg-gray-800/90 px-4 py-4 backdrop-blur-sm flex-shrink-0"
        >
          <div class="mx-auto w-full max-w-3xl">
            <form
              @submit.prevent="submit"
              class="rounded-[28px] border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-800 shadow-sm"
            >
              <div class="px-4 pt-3">
                <textarea
                  ref="inputRef"
                  v-model="input"
                  :disabled="loading"
                  rows="1"
                  placeholder="Add a reply..."
                  class="w-full border-0 bg-transparent px-0 py-1 text-sm text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:outline-none focus:ring-0 resize-none overflow-hidden leading-5"
                  @input="resizeInput"
                  @keydown.enter.exact.prevent="submit"
                  @keydown.enter.shift.exact.stop
                />
              </div>

              <div class="flex items-center justify-between px-3 pb-3 pt-2">
                <div class="flex items-center gap-2">
                  <button
                    type="button"
                    class="inline-flex items-center rounded-full border border-gray-200 dark:border-gray-600 px-3 py-1.5 text-xs font-medium text-gray-500 dark:text-gray-300 transition hover:border-gray-300 hover:text-gray-700 dark:hover:border-gray-500 dark:hover:text-white"
                    @click="clear"
                    :disabled="loading"
                    title="Clear history"
                  >
                    Clear
                  </button>
                  <span
                    class="hidden sm:inline-flex items-center gap-1 rounded-full bg-zesco-50 dark:bg-zesco-900/30 px-3 py-1.5 text-xs font-medium text-zesco-700 dark:text-zesco-300"
                  >
                    <span class="h-2 w-2 rounded-sm bg-zesco-500"></span>
                    {{ scopeLabel }}
                  </span>
                </div>

                <button
                  type="submit"
                  :disabled="loading || !input.trim()"
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
          </div>
        </div>
      </div>
    </aside>
  </div>
</template>

<script setup>
import { computed, ref, watch, nextTick } from "vue";
import { Link } from "@inertiajs/vue3";
import { useAiTasks } from "@/Composables/useAiTasks";
import {
  ArrowsPointingInIcon,
  ArrowsPointingOutIcon,
  XMarkIcon,
  ArrowTopRightOnSquareIcon,
} from "@heroicons/vue/20/solid";
import { ChatBubbleOvalLeftEllipsisIcon } from "@heroicons/vue/24/outline";

const props = defineProps({
  open: { type: Boolean, default: false },
  scope: { type: Object, default: null },
  sidebarOpen: { type: Boolean, default: false },
});

const emit = defineEmits(["close", "update:expanded"]);

const { aiPost } = useAiTasks();

const input = ref("");
const inputRef = ref(null);
const chatContainer = ref(null);
const loading = ref(false);
const chat = ref([]);
const provider = ref(null);
const statusError = ref("");
const expanded = ref(false);

const defaultSuggestions = computed(() => {
  const t = props.scope?.type;
  if (t === "pp_project")
    return [
      "What are the key risks for this project?",
      "Summarise the latest milestones",
      "Are there any budget concerns?",
      "What is the overall project status?",
    ];
  if (t === "pp_grid_studies")
    return [
      "What are pending grid impact studies?",
      "Summarise study outcomes",
      "Are any studies overdue?",
    ];
  return [
    "Give me an executive summary",
    "Which projects are at risk?",
    "What are the top budget concerns?",
    "Summarise recent progress",
  ];
});

const panelClasses = computed(() => {
  const base =
    "fixed bg-white dark:bg-gray-800 flex flex-col no-print transition-all duration-300 border-l border-gray-200 dark:border-gray-700";

  if (!props.open) {
    return [base, "inset-y-0 right-0 translate-x-full z-[60]"];
  }

  if (expanded.value) {
    return [
      base,
      "inset-0 z-[60] border-l-0 shadow-2xl",
      props.sidebarOpen
        ? "lg:left-64 lg:right-0 lg:w-auto lg:border-l lg:border-gray-200 lg:dark:border-gray-700"
        : "lg:left-20 lg:right-0 lg:w-auto lg:border-l lg:border-gray-200 lg:dark:border-gray-700",
    ];
  }

  // Mobile: fullscreen overlay
  // Desktop: right-side panel with fixed width
  return [
    base,
    "inset-0 z-[60] shadow-2xl", // mobile: fullscreen
    "lg:inset-y-0 lg:left-auto lg:right-0 lg:z-50 lg:shadow-none lg:w-96", // desktop: right side panel
  ];
});

function toggleExpand() {
  expanded.value = !expanded.value;
  emit("update:expanded", expanded.value);
}

const scopeLabel = computed(() => {
  const t = props.scope?.type;
  if (!t) return "No scope";
  if (t === "pp_project") return "PP · Project";
  if (t === "pp_grid_studies") return "PP · Grid Studies";
  if (t === "pp_explorer") return "PP · Explorer";
  return "PP · Portfolio";
});

async function loadStatus() {
  try {
    const resp = await fetch("/ai/status", {
      headers: { Accept: "application/json" },
    });
    if (!resp.ok) return;
    const data = await resp.json();
    provider.value = data.provider || null;
  } catch {
    // optional
  }
}

watch(
  () => props.open,
  (isOpen) => {
    if (isOpen) {
      statusError.value = "";
      loadStatus();
      nextTick(() => inputRef.value?.focus());
    }
  },
);

function scrollToBottom() {
  nextTick(() => {
    if (chatContainer.value) {
      chatContainer.value.scrollTop = chatContainer.value.scrollHeight;
    }
  });
}

function clear() {
  chat.value = [];
  input.value = "";
  nextTick(() => resizeInput());
  nextTick(() => inputRef.value?.focus());
}

function askSuggestion(s) {
  input.value = s;
  submit();
}

function payloadHistory() {
  return chat.value
    .filter((m) => m && (m.role === "user" || m.role === "assistant"))
    .map((m) => ({ role: m.role, content: m.content }))
    .slice(-12);
}

async function submit() {
  const q = input.value.trim();
  if (!q || loading.value) return;

  if (!props.scope) {
    statusError.value = "AI scope missing for this page.";
    return;
  }

  chat.value.push({ role: "user", content: q });
  input.value = "";
  loading.value = true;
  nextTick(() => resizeInput());
  scrollToBottom();

  try {
    const data = await aiPost("/api/ai/pp/query", {
      question: q,
      scope: props.scope,
      history: payloadHistory(),
    });

    const result = data?.result || data;

    if (result && result.answer) {
      chat.value.push({
        role: "assistant",
        content: result.answer,
        dataPoints: result.data_points || [],
        suggestions: result.follow_up_suggestions || [],
      });
    } else {
      chat.value.push({
        role: "assistant",
        content: data?.message || "Sorry, I could not generate an answer.",
      });
    }
  } catch (e) {
    chat.value.push({
      role: "assistant",
      content: e?.message || "Failed to connect to AI service.",
    });
  } finally {
    loading.value = false;
    scrollToBottom();
  }
}

function resizeInput() {
  const el = inputRef.value;
  if (!el) return;
  try {
    el.style.height = "auto";
    const maxHeight = 160;
    el.style.height = `${Math.min(el.scrollHeight, maxHeight)}px`;
  } catch {
    // ignore
  }
}
</script>
