<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessAiRequest;
use App\Services\AI\AiProviderManager;
use App\Services\AiAnalysisService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Inertia\Inertia;

class AiInsightsController extends Controller
{
    /**
     * AI Insights overview page.
     */
    public function index(AiProviderManager $ai)
    {
        return Inertia::render('AI/Insights', [
            'aiAvailable' => $ai->isAvailable(),
            'aiProvider' => $ai->isAvailable() ? $ai->getIdentifier() : null,
            'queueDriver' => config('queue.default'),
        ]);
    }

    // ─────────────────────────────────────────────────────────
    //  Async dispatch + poll approach
    // ─────────────────────────────────────────────────────────

    /**
     * Dispatch any AI method as a background job.
     * Returns a task ID that the frontend polls.
     */
    private function dispatchAiTask(string $method, array $params = []): JsonResponse
    {
        $taskId = Str::uuid()->toString();

        try {
            // Seed the cache entry so the poll endpoint finds it immediately
            Cache::put("ai_task:{$taskId}", [
                'status' => 'queued',
                'queued_at' => now()->toISOString(),
            ], now()->addMinutes(30));

            // Dispatch to the configured queue connection (often 'database')
            ProcessAiRequest::dispatch($taskId, $method, $params);
        } catch (\Throwable $e) {
            // Avoid leaving a stuck task entry behind
            Cache::forget("ai_task:{$taskId}");
            throw $e;
        }

        return response()->json([
            'async' => true,
            'task_id' => $taskId,
        ]);
    }

    /**
     * Poll for a background AI task result.
     * GET /api/ai/task/{taskId}
     */
    public function pollTask(string $taskId): JsonResponse
    {
        $data = Cache::get("ai_task:{$taskId}");

        if (!$data) {
            return response()->json([
                'status' => 'not_found',
                'message' => 'Task not found or expired.',
            ], 404);
        }

        return response()->json($data);
    }

    // ─────────────────────────────────────────────────────────
    //  Sync-or-async wrappers for each AI feature
    // ─────────────────────────────────────────────────────────

    /**
     * Should we dispatch to queue? Yes when queue driver is not 'sync'.
     * When running `sync` driver, we still bump set_time_limit.
     */
    private function shouldAsync(): bool
    {
        $driver = config('queue.default');

        if ($driver === 'sync') {
            return false;
        }

        // If using the database queue but migrations haven't been run,
        // dispatching will fail (missing `jobs` table). Fall back to sync.
        if ($driver === 'database') {
            try {
                return Schema::hasTable('jobs');
            } catch (\Throwable) {
                return false;
            }
        }

        return true;
    }

    /**
     * Enforce Planning & Projects (PP) access rules for PP-scoped AI endpoints.
     * Mirrors PP dashboard access: only PP directorate head or admin.
     */
    private function enforcePpAccess(Request $request): void
    {
        $user = $request->user();
        if (!$user) {
            abort(401);
        }

        if ($user->isAdmin()) {
            return;
        }

        $isDirectorateHead = ($user->role?->name ?? null) === 'directorate_head';
        $isPp = ($user->directorate?->code ?? null) === 'PP';

        if (!$isDirectorateHead || !$isPp) {
            abort(403, 'You do not have access to PP portfolio AI.');
        }
    }

    /**
     * Generate executive AI insights.
     */
    public function executiveInsights(Request $request, AiAnalysisService $aiService): JsonResponse
    {
        if (!$aiService->isAvailable()) {
            return response()->json([
                'success' => false,
                'message' => 'AI service unavailable. Check your AI provider configuration.',
            ], 503);
        }

        $fresh = $request->boolean('fresh', false);

        if ($this->shouldAsync()) {
            try {
                return $this->dispatchAiTask('executiveInsights', ['fresh' => $fresh]);
            } catch (\Throwable $e) {
                Log::warning('AI async dispatch failed; falling back to sync', [
                    'method' => 'executiveInsights',
                    'error' => $e->getMessage(),
                ]);
                // fall through to sync
            }
        }

        // Synchronous fallback — extend PHP time limit
        set_time_limit(0);

        try {
            $insights = $aiService->generateExecutiveInsights($fresh);
        } catch (\Throwable $e) {
            report($e);
            return response()->json([
                'success' => false,
                'message' => 'AI analysis failed: ' . $e->getMessage(),
            ], 500);
        }

        return response()->json([
            'success' => !empty($insights),
            'insights' => $insights,
        ]);
    }

    /**
     * Explain a KPI anomaly.
     */
    public function explainAnomaly(Request $request, AiAnalysisService $aiService): JsonResponse
    {
        $request->validate([
            'kpi_id' => 'required|integer|exists:kpis,id',
            'directorate_id' => 'required|integer|exists:directorates,id',
        ]);

        if (!$aiService->isAvailable()) {
            return response()->json(['success' => false, 'message' => 'AI service unavailable.'], 503);
        }

        if ($this->shouldAsync()) {
            try {
                return $this->dispatchAiTask('explainAnomaly', $request->only('kpi_id', 'directorate_id'));
            } catch (\Throwable $e) {
                Log::warning('AI async dispatch failed; falling back to sync', [
                    'method' => 'explainAnomaly',
                    'error' => $e->getMessage(),
                ]);
            }
        }

        set_time_limit(0);

        try {
            $explanation = $aiService->explainAnomaly($request->kpi_id, $request->directorate_id);
        } catch (\Throwable $e) {
            report($e);
            return response()->json([
                'success' => false,
                'message' => 'AI analysis failed: ' . $e->getMessage(),
            ], 500);
        }

        return response()->json([
            'success' => !empty($explanation),
            'explanation' => $explanation,
        ]);
    }

    /**
     * Get AI recommendations for a directorate.
     */
    public function recommendations(Request $request, AiAnalysisService $aiService): JsonResponse
    {
        $request->validate([
            'directorate_id' => 'required|integer|exists:directorates,id',
        ]);

        if (!$aiService->isAvailable()) {
            return response()->json(['success' => false, 'message' => 'AI service unavailable.'], 503);
        }

        if ($this->shouldAsync()) {
            try {
                return $this->dispatchAiTask('recommendations', $request->only('directorate_id'));
            } catch (\Throwable $e) {
                Log::warning('AI async dispatch failed; falling back to sync', [
                    'method' => 'recommendations',
                    'error' => $e->getMessage(),
                ]);
            }
        }

        set_time_limit(0);

        try {
            $recommendations = $aiService->suggestActions($request->directorate_id);
        } catch (\Throwable $e) {
            report($e);
            return response()->json([
                'success' => false,
                'message' => 'AI analysis failed: ' . $e->getMessage(),
            ], 500);
        }

        return response()->json([
            'success' => !empty($recommendations),
            'recommendations' => $recommendations,
        ]);
    }

    /**
     * Natural language query.
     */
    public function query(Request $request, AiAnalysisService $aiService): JsonResponse
    {
        $request->validate([
            'question' => 'required|string|max:1000',
        ]);

        if (!$aiService->isAvailable()) {
            return response()->json(['success' => false, 'message' => 'AI service unavailable.'], 503);
        }

        if ($this->shouldAsync()) {
            try {
                return $this->dispatchAiTask('query', ['question' => $request->question]);
            } catch (\Throwable $e) {
                Log::warning('AI async dispatch failed; falling back to sync', [
                    'method' => 'query',
                    'error' => $e->getMessage(),
                ]);
            }
        }

        set_time_limit(0);

        try {
            $answer = $aiService->answerQuery($request->question);
        } catch (\Throwable $e) {
            report($e);
            return response()->json([
                'success' => false,
                'message' => 'AI analysis failed: ' . $e->getMessage(),
            ], 500);
        }

        return response()->json([
            'success' => !empty($answer),
            'result' => $answer,
        ]);
    }

    /**
     * Natural language query, scoped to Planning & Projects (PP) portfolio pages.
     * POST /api/ai/pp/query
     */
    public function ppQuery(Request $request, AiAnalysisService $aiService): JsonResponse
    {
        $this->enforcePpAccess($request);

        $request->validate([
            'question' => 'required|string|max:1000',
            'scope' => 'required|array',
            'scope.type' => 'required|string|max:50',
            'scope.directorate_id' => 'nullable|integer|exists:directorates,id',
            'scope.pp_project_id' => 'nullable|integer|exists:pp_projects,id',
            'scope.filters' => 'nullable|array',
            'history' => 'nullable|array|max:12',
            'history.*.role' => 'required_with:history|string|in:user,assistant',
            'history.*.content' => 'required_with:history|string|max:1000',
        ]);

        if (!$aiService->isAvailable()) {
            return response()->json(['success' => false, 'message' => 'AI service unavailable.'], 503);
        }

        $scope = $request->input('scope', []);
        $question = $request->string('question')->toString();
        $history = $request->input('history', []);

        if ($this->shouldAsync()) {
            try {
                return $this->dispatchAiTask('ppQuery', [
                    'scope' => $scope,
                    'question' => $question,
                    'history' => $history,
                ]);
            } catch (\Throwable $e) {
                Log::warning('AI async dispatch failed; falling back to sync', [
                    'method' => 'ppQuery',
                    'error' => $e->getMessage(),
                ]);
            }
        }

        set_time_limit(0);

        try {
            $answer = $aiService->answerPpScopedQuery($scope, $question, $history);
        } catch (\Throwable $e) {
            report($e);
            return response()->json([
                'success' => false,
                'message' => 'AI analysis failed: ' . $e->getMessage(),
            ], 500);
        }

        return response()->json([
            'success' => !empty($answer),
            'result' => $answer,
        ]);
    }

    /**
     * Predict deadline breach for a KPI.
     */
    public function predictBreach(Request $request, AiAnalysisService $aiService): JsonResponse
    {
        $request->validate([
            'kpi_id' => 'required|integer|exists:kpis,id',
            'directorate_id' => 'required|integer|exists:directorates,id',
        ]);

        if (!$aiService->isAvailable()) {
            return response()->json(['success' => false, 'message' => 'AI service unavailable.'], 503);
        }

        if ($this->shouldAsync()) {
            try {
                return $this->dispatchAiTask('predictBreach', $request->only('kpi_id', 'directorate_id'));
            } catch (\Throwable $e) {
                Log::warning('AI async dispatch failed; falling back to sync', [
                    'method' => 'predictBreach',
                    'error' => $e->getMessage(),
                ]);
            }
        }

        set_time_limit(0);

        try {
            $prediction = $aiService->predictDeadlineBreach($request->kpi_id, $request->directorate_id);
        } catch (\Throwable $e) {
            report($e);
            return response()->json([
                'success' => false,
                'message' => 'AI analysis failed: ' . $e->getMessage(),
            ], 500);
        }

        return response()->json([
            'success' => !empty($prediction),
            'prediction' => $prediction,
        ]);
    }

    /**
     * Check AI provider status.
     */
    public function status(AiProviderManager $ai): JsonResponse
    {
        return response()->json([
            'available' => $ai->isAvailable(),
            'provider' => $ai->isAvailable() ? $ai->getIdentifier() : null,
            'configured_provider' => config('dashboard.ai.provider'),
            'enabled' => config('dashboard.ai.enabled', true),
            'async' => $this->shouldAsync(),
        ]);
    }

    /**
     * Clear AI caches.
     */
    public function clearCache(AiAnalysisService $aiService): JsonResponse
    {
        $aiService->clearCache();

        return response()->json([
            'success' => true,
            'message' => 'AI analysis cache cleared.',
        ]);
    }
}
