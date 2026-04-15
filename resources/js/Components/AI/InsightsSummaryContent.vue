<template>
    <!-- Error -->
    <div v-if="error && !insightsLoading" class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-900/40 dark:bg-red-900/20 dark:text-red-200">
        <p class="font-medium">Couldn’t generate insights.</p>
        <p class="mt-1 text-xs opacity-90">{{ error }}</p>
        <button @click="$emit('generate')" :disabled="!aiAvailable"
                class="mt-3 inline-flex items-center gap-2 rounded-lg bg-zesco-500 px-3 py-2 text-xs font-medium text-white hover:bg-zesco-600 transition disabled:opacity-50">
            Try again
        </button>
    </div>

    <!-- Loading -->
    <div v-if="insightsLoading && !insights" class="flex-1 flex items-center justify-center">
        <div class="text-center">
            <div class="flex justify-center gap-1 mb-3">
                <span class="w-1.5 h-1.5 rounded-full bg-zesco-400 animate-bounce" style="animation-delay: 0ms" />
                <span class="w-1.5 h-1.5 rounded-full bg-zesco-400 animate-bounce" style="animation-delay: 150ms" />
                <span class="w-1.5 h-1.5 rounded-full bg-zesco-400 animate-bounce" style="animation-delay: 300ms" />
            </div>
            <p class="text-sm text-gray-500">Generating insights<span v-if="elapsedInsights"> ({{ elapsedInsights }}s)</span>…</p>
            <p class="text-xs text-gray-400 mt-1">Large models can take a few minutes</p>
        </div>
    </div>

    <!-- Content -->
    <div v-else-if="insights" class="space-y-5">
        <div>
            <p class="text-sm text-gray-700 dark:text-gray-300 leading-relaxed whitespace-pre-wrap">{{ insights.summary }}</p>
        </div>

        <!-- AI Charts -->
        <div v-if="insights.charts && insights.charts.length" class="space-y-3">
            <AiChart v-for="(chart, i) in insights.charts" :key="i" :chart="chart" />
        </div>

        <div v-if="insights.key_concerns?.length">
            <h4 class="text-xs font-semibold text-red-600 dark:text-red-400 uppercase tracking-wider mb-2">Key Concerns</h4>
            <ul class="space-y-1">
                <li v-for="c in insights.key_concerns" :key="c" class="text-sm text-gray-600 dark:text-gray-400 flex items-start gap-2">
                    <span class="text-red-400 mt-0.5">•</span> {{ c }}
                </li>
            </ul>
        </div>

        <div v-if="insights.positive_highlights?.length">
            <h4 class="text-xs font-semibold text-green-600 dark:text-green-400 uppercase tracking-wider mb-2">Positive Highlights</h4>
            <ul class="space-y-1">
                <li v-for="h in insights.positive_highlights" :key="h" class="text-sm text-gray-600 dark:text-gray-400 flex items-start gap-2">
                    <span class="text-green-400 mt-0.5">•</span> {{ h }}
                </li>
            </ul>
        </div>

        <div v-if="insights.recommendations?.length">
            <h4 class="text-xs font-semibold text-blue-600 dark:text-blue-400 uppercase tracking-wider mb-2">Recommendations</h4>
            <ul class="space-y-1">
                <li v-for="r in insights.recommendations" :key="r" class="text-sm text-gray-600 dark:text-gray-400 flex items-start gap-2">
                    <span class="text-blue-400 mt-0.5">→</span> {{ r }}
                </li>
            </ul>
        </div>

        <div v-if="insights.outlook" class="pt-3 border-t border-gray-100 dark:border-gray-700">
            <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Outlook</h4>
            <p class="text-sm text-gray-600 dark:text-gray-400 italic">{{ insights.outlook }}</p>
        </div>
    </div>

    <!-- Empty -->
    <div v-else class="flex-1 flex items-center justify-center">
    <div class="text-center">
        <p class="text-sm text-gray-400 mb-3">No insights generated yet.</p>
        <button @click="$emit('generate')" :disabled="!aiAvailable"
                class="inline-flex items-center gap-2 rounded-lg bg-zesco-500 px-4 py-2 text-sm font-medium text-white hover:bg-zesco-600 transition disabled:opacity-50">
            Generate Insights
        </button>
    </div>
    </div>
</template>

<script setup>
import AiChart from './AiChart.vue';

defineProps({
    insights: { type: Object, default: null },
    insightsLoading: { type: Boolean, default: false },
    elapsedInsights: { type: Number, default: 0 },
    aiAvailable: { type: Boolean, default: false },
    error: { type: String, default: '' },
});

defineEmits(['generate']);
</script>
