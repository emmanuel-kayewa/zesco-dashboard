<?php

namespace App\Services;

use App\Models\Alert;
use App\Models\Directorate;
use App\Models\Kpi;
use App\Models\KpiEntry;
use App\Models\Incident;
use App\Models\PpProject;
use App\Models\Risk;
use App\Models\WayleaveEntry;
use App\Services\AI\AiProviderManager;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class AiAnalysisService
{
    private AiProviderManager $ai;
    private DashboardService $dashboard;
    private PpDashboardService $ppDashboard;

    public function __construct(AiProviderManager $ai, DashboardService $dashboard, PpDashboardService $ppDashboard)
    {
        $this->ai = $ai;
        $this->dashboard = $dashboard;
        $this->ppDashboard = $ppDashboard;
    }

    /**
     * Check if AI analysis is available.
     */
    public function isAvailable(): bool
    {
        return config('dashboard.ai.enabled', true) && $this->ai->isAvailable();
    }

    // ═══════════════════════════════════════════════════════════
    //  A — Executive Insights
    // ═══════════════════════════════════════════════════════════

    /**
     * Generate an AI-written executive summary with key insights and concerns.
     */
    public function generateExecutiveInsights(bool $fresh = false): array
    {
        $cacheKey = 'ai_executive_insights';
        $ttl = config('dashboard.ai.cache.executive_insights', 1440);

        if (!$fresh && $cached = $this->getCache($cacheKey)) {
            return $cached;
        }

        $contextData = $this->gatherExecutiveContext();

        $systemPrompt = <<<'PROMPT'
You are a senior business intelligence analyst at ZESCO Limited, Zambia's primary electricity utility company.
Analyze the provided KPI data and generate an executive briefing.

CRITICAL: Every value in your JSON must be the correct type. "summary", "risk_assessment", and "outlook" MUST be plain-text strings (human-readable paragraphs), NOT objects or arrays. "key_concerns", "positive_highlights", and "recommendations" MUST be arrays of strings.

Your response must be valid JSON with EXACTLY this structure:
{
  "summary": "A 2-3 paragraph executive narrative (MUST be a string, not an object) highlighting overall performance across all directorates. Reference specific KPI values, directorate names, and percentages.",
  "key_concerns": ["Each concern is a plain-text sentence string", "Another concern string"],
  "positive_highlights": ["Each highlight is a plain-text sentence string", "Another highlight string"],
  "recommendations": ["Each recommendation is a plain-text sentence string", "Another recommendation string"],
  "risk_assessment": "A single paragraph string summarising overall risk posture (MUST be a string, not an object)",
  "outlook": "A single paragraph string with forward-looking statement about trends (MUST be a string, not an object)",
  "charts": [
    {
      "type": "horizontal_bar",
      "title": "Directorate KPI Completion",
      "labels": ["Directorate A", "Directorate B"],
      "values": [85, 72],
      "series_name": "Completion %"
    }
  ]
}

Chart guidelines:
- Include 1-3 charts that visualise the most important data points from your analysis
- Supported chart types: "bar", "horizontal_bar", "line", "pie", "gauge"
- For gauge charts use: { "type": "gauge", "title": "...", "value": 72, "max": 100, "unit": "%", "series_name": "..." }
- Use real directorate names and actual KPI values from the data
- Keep labels short (max 15 chars) so they fit in a sidebar
- Choose chart types that best represent the data: horizontal_bar for comparisons, pie for proportions, gauge for single scores, line for trends

Be specific with numbers. Reference actual directorate names and KPI values. Be concise but insightful.
PROMPT;

        $result = $this->ai->provider()->chatWithJson($systemPrompt, json_encode($contextData));

        if (!empty($result)) {
            // Normalize keys — the LLM sometimes returns alternate key names
            $result = $this->normalizeExecutiveInsights($result);
            $result['generated_at'] = now()->toISOString();
            $result['provider'] = $this->ai->getIdentifier();
            $this->setCache($cacheKey, $result, $ttl);
        }

        return $result;
    }

    // ═══════════════════════════════════════════════════════════
    //  B — Anomaly Explanation
    // ═══════════════════════════════════════════════════════════

    /**
     * Explain why a KPI anomaly occurred by cross-referencing related data.
     */
    public function explainAnomaly(int $kpiId, int $directorateId): array
    {
        $cacheKey = "ai_anomaly_{$kpiId}_{$directorateId}";
        $ttl = config('dashboard.ai.cache.anomaly_explanation', 720);

        if ($cached = $this->getCache($cacheKey)) {
            return $cached;
        }

        $context = $this->gatherAnomalyContext($kpiId, $directorateId);

        $systemPrompt = <<<'PROMPT'
You are a data analyst at ZESCO Limited (Zambia electricity utility).
A KPI anomaly has been detected. Analyze all the surrounding data to explain WHY this anomaly likely occurred.

Cross-reference with:
- Other KPIs in the same directorate
- Recent incidents or risks
- Historical trends

Return valid JSON:
{
  "likely_causes": ["cause 1 with explanation", "cause 2 with explanation"],
  "correlated_factors": ["factor 1", "factor 2"],
  "severity_assessment": "low|medium|high|critical",
  "recommended_actions": ["action 1", "action 2"],
  "confidence": "low|medium|high"
}
PROMPT;

        $result = $this->ai->provider()->chatWithJson($systemPrompt, json_encode($context));

        if (!empty($result)) {
            $result['kpi_id'] = $kpiId;
            $result['directorate_id'] = $directorateId;
            $result['generated_at'] = now()->toISOString();
            $this->setCache($cacheKey, $result, $ttl);
        }

        return $result;
    }

    // ═══════════════════════════════════════════════════════════
    //  C — Directorate Recommendations
    // ═══════════════════════════════════════════════════════════

    /**
     * Generate actionable suggestions for a directorate.
     */
    public function suggestActions(int $directorateId): array
    {
        $cacheKey = "ai_recommendations_{$directorateId}";
        $ttl = config('dashboard.ai.cache.recommendations', 720);

        if ($cached = $this->getCache($cacheKey)) {
            return $cached;
        }

        $context = $this->gatherDirectorateContext($directorateId);

        $systemPrompt = <<<'PROMPT'
You are a strategic advisor to ZESCO Limited's management.
Analyze the directorate's performance data and provide prioritized, actionable recommendations.

Return valid JSON:
{
  "performance_rating": "excellent|good|needs_attention|critical",
  "summary": "2-3 sentence performance summary",
  "priority_actions": [
    {
      "action": "specific action to take",
      "impact": "expected impact",
      "urgency": "immediate|this_week|this_month|this_quarter",
      "related_kpi": "KPI name this affects"
    }
  ],
  "strengths": ["strength 1", "strength 2"],
  "risks_ahead": ["risk 1", "risk 2"]
}
PROMPT;

        $result = $this->ai->provider()->chatWithJson($systemPrompt, json_encode($context));

        if (!empty($result)) {
            $result['directorate_id'] = $directorateId;
            $result['generated_at'] = now()->toISOString();
            $this->setCache($cacheKey, $result, $ttl);
        }

        return $result;
    }

    // ═══════════════════════════════════════════════════════════
    //  D — Natural Language Query
    // ═══════════════════════════════════════════════════════════

    /**
     * Answer a natural language question about the dashboard data.
     */
    public function answerQuery(string $question): array
    {
        $context = $this->gatherQueryContext();

        $systemPrompt = <<<'PROMPT'
You are a helpful data analyst assistant for ZESCO Limited's executive dashboard.
Answer the user's question using the provided dashboard data. Be specific with numbers, directorate names, and KPI values.

The data includes KPI metrics, financial summaries, wayleave/survey processing data (applications received vs cleared by aspect/type), and recent alerts.

If the question cannot be answered with the available data, say so clearly.

Return valid JSON:
{
  "answer": "your detailed answer to the question",
  "data_points": ["key data point 1", "key data point 2"],
  "confidence": "high|medium|low",
  "follow_up_suggestions": ["related question the user might want to ask"],
  "charts": []
}

Chart guidelines (optional — only include when a visualisation genuinely helps the user):
- Add a "charts" array with 0-2 charts when the answer involves comparisons, distributions, or trends
- Supported types: "bar", "horizontal_bar", "line", "pie", "gauge"
- Bar/line/pie format: { "type": "bar", "title": "...", "labels": [...], "values": [...], "series_name": "..." }
- Gauge format: { "type": "gauge", "title": "...", "value": 72, "max": 100, "unit": "%" }
- Keep labels short (max 15 chars). Use real data from the context.
- If the question is simple or text-only answers suffice, leave "charts" as an empty array.
PROMPT;

        $userPrompt = "QUESTION: {$question}\n\nAVAILABLE DATA:\n" . json_encode($context);

        return $this->ai->provider()->chatWithJson($systemPrompt, $userPrompt);
    }

    /**
     * Answer a natural language question scoped to PP portfolio data.
     *
     * @param  array  $scope  Expected keys: type, directorate_id?, filters?, pp_project_id?
     * @param  array  $history  Optional array of { role: user|assistant, content: string }
     */
    public function answerPpScopedQuery(array $scope, string $question, array $history = []): array
    {
        $context = $this->gatherPpQueryContext($scope);

        $systemPrompt = <<<'PROMPT'
You are a helpful data analyst assistant for ZESCO Limited's Planning & Projects (PP) portfolio dashboard.

You MUST answer ONLY using the provided PP portfolio data (and the current PP scope). Do not invent figures.
If the user asks to compare PP to other directorates or asks about non-PP directorates, say you cannot do cross-directorate comparisons in this view and suggest using the AI Insights page instead.

Return valid JSON:
{
  "answer": "your detailed answer to the question",
  "data_points": ["key data point 1", "key data point 2"],
  "confidence": "high|medium|low",
  "follow_up_suggestions": ["related question the user might want to ask"]
}
PROMPT;

        $historyText = $this->formatChatHistoryForPrompt($history, 10);

        $userPrompt = "SCOPE:\n" . json_encode($context['scope'] ?? new \stdClass()) .
            "\n\nPP DASHBOARD DATA:\n" . json_encode($context['data'] ?? new \stdClass()) .
            ($historyText ? "\n\nCONVERSATION SO FAR:\n{$historyText}" : '') .
            "\n\nQUESTION: {$question}";

        return $this->ai->provider()->chatWithJson($systemPrompt, $userPrompt, [
            'temperature' => 0.2,
            'max_tokens' => 4096,
        ]);
    }

    private function formatChatHistoryForPrompt(array $history, int $maxMessages = 10): string
    {
        if (empty($history)) {
            return '';
        }

        $clean = [];
        foreach ($history as $m) {
            if (!is_array($m)) continue;
            $role = $m['role'] ?? null;
            $content = $m['content'] ?? null;
            if (!in_array($role, ['user', 'assistant'], true)) continue;
            if (!is_string($content) || $content === '') continue;
            $clean[] = [
                'role' => $role,
                'content' => mb_substr($content, 0, 1000),
            ];
        }

        $clean = array_slice($clean, max(0, count($clean) - $maxMessages));

        $lines = [];
        foreach ($clean as $m) {
            $label = $m['role'] === 'user' ? 'User' : 'Assistant';
            $lines[] = $label . ': ' . str_replace(["\r\n", "\n"], ' ', $m['content']);
        }

        return implode("\n", $lines);
    }

    /**
     * Gather PP context for the given scope. Keeps the payload compact.
     */
    private function gatherPpQueryContext(array $scope): array
    {
        $type = (string)($scope['type'] ?? 'pp_portfolio');
        $filters = is_array($scope['filters'] ?? null) ? $scope['filters'] : [];
        $ppProjectId = isset($scope['pp_project_id']) ? (int)$scope['pp_project_id'] : null;

        $cacheKey = 'ai_pp_context:' . sha1(json_encode([
            'type' => $type,
            'filters' => $filters,
            'pp_project_id' => $ppProjectId,
        ]));

        $ttlMinutes = (int)config('dashboard.ai.cache.pp_context', 10);

        $data = Cache::remember($cacheKey, now()->addMinutes($ttlMinutes), function () use ($type, $filters, $ppProjectId) {
            if ($type === 'pp_project' && $ppProjectId) {
                $project = PpProject::find($ppProjectId);
                if (!$project) {
                    return ['type' => $type, 'project' => null];
                }

                $detail = $this->ppDashboard->getProjectDetail($project);

                return [
                    'type' => $type,
                    'project' => [
                        'project' => $detail['project'] ?? null,
                        'summary' => $detail['summary'] ?? null,
                        'milestones' => array_slice($detail['milestones'] ?? [], 0, 12),
                        'financials' => array_slice($detail['financials'] ?? [], 0, 10),
                        'risks' => array_slice($detail['risks'] ?? [], 0, 10),
                        'safeguards' => array_slice($detail['safeguards'] ?? [], 0, 10),
                        'gridStudies' => array_slice($detail['gridStudies'] ?? [], 0, 10),
                    ],
                ];
            }

            if ($type === 'pp_grid_studies') {
                $grid = $this->ppDashboard->getGridStudiesData($filters);
                return [
                    'type' => $type,
                    'appliedFilters' => $grid['appliedFilters'] ?? [],
                    'kpis' => $grid['kpis'] ?? null,
                    'stageFunnel' => $grid['stageFunnel'] ?? null,
                    'techPie' => array_slice($grid['techPie'] ?? [], 0, 12),
                    'areaBreakdown' => array_slice($grid['areaBreakdown'] ?? [], 0, 12),
                    'typeBreakdown' => array_slice($grid['typeBreakdown'] ?? [], 0, 12),
                    'studiesSample' => array_slice($grid['studies'] ?? [], 0, 25),
                    'totalCount' => $grid['totalCount'] ?? null,
                ];
            }

            if ($type === 'pp_explorer') {
                $explorer = $this->ppDashboard->getExplorerData($filters);
                $projects = $explorer['projects'] ?? [];
                if (is_array($projects)) {
                    $projects = array_slice($projects, 0, 25);
                }

                $breakdowns = $explorer['breakdowns'] ?? [];
                if (is_array($breakdowns)) {
                    // Keep only small portions of each breakdown
                    foreach ($breakdowns as $k => $v) {
                        if (isset($v['data']) && is_array($v['data'])) {
                            $breakdowns[$k]['data'] = array_slice($v['data'], 0, 12);
                        }
                    }
                }

                return [
                    'type' => $type,
                    'appliedFilters' => $explorer['appliedFilters'] ?? [],
                    'kpis' => $explorer['kpis'] ?? null,
                    'totalCount' => $explorer['totalCount'] ?? null,
                    'breakdowns' => $breakdowns,
                    'projectsSample' => $projects,
                    'risksByCategory' => array_slice($explorer['risksByCategory'] ?? [], 0, 10),
                    'risksByLevel' => array_slice($explorer['risksByLevel'] ?? [], 0, 10),
                ];
            }

            // Default: portfolio overview
            $overview = $this->ppDashboard->getOverview();
            return [
                'type' => 'pp_portfolio',
                'kpis' => $overview['kpis'] ?? null,
                'sectorCards' => array_slice($overview['sectorCards'] ?? [], 0, 10),
                'sectorBreakdown' => array_slice($overview['sectorBreakdown'] ?? [], 0, 10),
                'statusBreakdown' => array_slice($overview['statusBreakdown'] ?? [], 0, 10),
                'ragBreakdown' => array_slice($overview['ragBreakdown'] ?? [], 0, 10),
                'programmeBreakdown' => array_slice($overview['programmeBreakdown'] ?? [], 0, 10),
                'gridStudiesSummary' => $overview['gridStudiesSummary'] ?? null,
                'recentIssues' => array_slice($overview['recentIssues'] ?? [], 0, 10),
            ];
        });

        // If the context grows too large, reduce further.
        $encoded = json_encode($data);
        if (is_string($encoded) && strlen($encoded) > 60000) {
            if (isset($data['projectsSample']) && is_array($data['projectsSample'])) {
                $data['projectsSample'] = array_slice($data['projectsSample'], 0, 10);
            }
            if (isset($data['studiesSample']) && is_array($data['studiesSample'])) {
                $data['studiesSample'] = array_slice($data['studiesSample'], 0, 10);
            }
        }

        return [
            'scope' => [
                'type' => $type,
                'filters' => $filters,
                'pp_project_id' => $ppProjectId,
            ],
            'data' => $data,
            'generated_at' => now()->toISOString(),
            'provider' => $this->ai->getIdentifier(),
        ];
    }

    // ═══════════════════════════════════════════════════════════
    //  E — Predictive Deadline Breach
    // ═══════════════════════════════════════════════════════════

    /**
     * Assess whether a KPI is likely to miss its deadline.
     */
    public function predictDeadlineBreach(int $kpiId, int $directorateId): array
    {
        $kpi = Kpi::find($kpiId);
        if (!$kpi || !$kpi->hasDeadline()) {
            return [];
        }

        $entries = KpiEntry::where('kpi_id', $kpiId)
            ->where('directorate_id', $directorateId)
            ->orderBy('period_date')
            ->get(['value', 'period_date', 'period_type'])
            ->toArray();

        // Also get the linear forecast
        $forecast = $this->dashboard->forecastKpi($kpiId, $directorateId);

        $context = [
            'kpi_name' => $kpi->name,
            'target_value' => $kpi->target_value,
            'target_deadline' => $kpi->target_deadline?->format('Y-m-d'),
            'days_remaining' => $kpi->daysUntilDeadline(),
            'current_milestone' => $kpi->getCurrentMilestone(),
            'trend_direction' => $kpi->trend_direction,
            'unit' => $kpi->unit,
            'historical_entries' => $entries,
            'linear_forecast' => $forecast,
        ];

        $systemPrompt = <<<'PROMPT'
You are a predictive analytics specialist at ZESCO Limited.
Assess whether the given KPI is likely to meet its target by the deadline.

Consider: the trend in historical data, the linear forecast, remaining time, and the gap between current value and target.

Return valid JSON:
{
  "will_meet_target": true/false,
  "probability_of_meeting": 0-100,
  "projected_value_at_deadline": number,
  "gap_analysis": "explanation of the gap between projected and target",
  "acceleration_needed": "what rate of improvement is needed to meet the target",
  "recommendation": "specific action recommendation"
}
PROMPT;

        return $this->ai->provider()->chatWithJson($systemPrompt, json_encode($context));
    }

    // ═══════════════════════════════════════════════════════════
    //  F — KPI Auto-Categorization (for import)
    // ═══════════════════════════════════════════════════════════

    /**
     * Auto-categorize and enrich imported KPI data using AI.
     *
     * @param  array  $rawKpis  Array of raw KPI data (name, description, etc.)
     * @return array  Enriched KPI data with suggested categories, thresholds, etc.
     */
    public function categorizeKpis(array $rawKpis): array
    {
        $categories = implode(', ', array_keys(config('dashboard.kpi_categories', [])));

        $systemPrompt = <<<PROMPT
You are a KPI classification expert for ZESCO Limited, a Zambian electricity utility company.
Given a list of KPIs, classify each one and suggest appropriate settings.

Available categories: {$categories}
Available units: number, percentage, currency, ratio
Available trend directions: up_is_good, down_is_good, neutral

For each KPI, return:
{
  "kpis": [
    {
      "original_name": "the name as provided",
      "suggested_name": "cleaned/standardized name",
      "category": "one of the categories above",
      "unit": "one of the units above",
      "trend_direction": "up_is_good or down_is_good or neutral",
      "suggested_target": number or null,
      "suggested_warning_threshold": number or null,
      "suggested_critical_threshold": number or null,
      "currency_code": "ZMW or USD or null",
      "description": "brief description of what this KPI measures"
    }
  ]
}
PROMPT;

        $result = $this->ai->provider()->chatWithJson($systemPrompt, json_encode(['kpis' => $rawKpis]));

        return $result['kpis'] ?? [];
    }

    // ═══════════════════════════════════════════════════════════
    //  G — Weekly Digest
    // ═══════════════════════════════════════════════════════════

    /**
     * Generate a weekly performance digest for email.
     */
    public function generateWeeklyDigest(): array
    {
        $context = $this->gatherWeeklyContext();

        $systemPrompt = <<<'PROMPT'
You are ZESCO Limited's AI performance analyst.
Generate a concise weekly performance digest for senior leadership.

Return valid JSON:
{
  "headline": "one-line headline for the week",
  "executive_summary": "2-3 paragraph summary of the week's performance",
  "top_performers": [{"directorate": "name", "highlight": "what they did well"}],
  "areas_of_concern": [{"directorate": "name", "concern": "what needs attention"}],
  "kpi_movements": [{"kpi": "name", "movement": "description of change"}],
  "week_ahead_outlook": "what to watch for next week"
}
PROMPT;

        return $this->ai->provider()->chatWithJson($systemPrompt, json_encode($context));
    }

    // ═══════════════════════════════════════════════════════════
    //  Context Gathering (Private)
    // ═══════════════════════════════════════════════════════════

    /**
     * Normalize executive insight keys so the frontend always receives a consistent schema.
     * The LLM sometimes returns alternate key names or wrong types (objects instead of strings).
     */
    private function normalizeExecutiveInsights(array $result): array
    {
        // Use the most narrative-sounding string as summary — prefer 'outlook' over data-dumps
        if (empty($result['summary']) || !is_string($result['summary'])) {
            $result['summary'] = $this->pickString($result, ['summary', 'executive_summary', 'overall_summary', 'overview', 'outlook', 'risk_summary', 'trend_analysis']);
        }

        // Ensure 'key_concerns' is an array of strings — extract from structured issues if needed
        if (empty($result['key_concerns']) || !$this->isStringList($result['key_concerns'])) {
            $concerns = $this->pickStringArray($result, ['key_concerns', 'concerns', 'risks', 'risk_factors']);
            if (empty($concerns)) {
                $concerns = $this->extractIssueStrings($result, ['outstanding_issues', 'outstanding_anomalies', 'outstanding_data_issues']);
            }
            $result['key_concerns'] = $concerns;
        }

        // Ensure 'positive_highlights' is an array of strings
        if (empty($result['positive_highlights']) || !$this->isStringList($result['positive_highlights'])) {
            $result['positive_highlights'] = $this->pickStringArray($result, ['positive_highlights', 'highlights', 'positives', 'achievements']);
        }

        // Ensure 'recommendations' is an array of strings
        if (empty($result['recommendations']) || !$this->isStringList($result['recommendations'])) {
            $result['recommendations'] = $this->pickStringArray($result, ['recommendations', 'suggested_actions', 'actions']);
        }

        // Ensure 'risk_assessment' is a string
        if (empty($result['risk_assessment']) || !is_string($result['risk_assessment'])) {
            $result['risk_assessment'] = $this->pickString($result, ['risk_assessment', 'risk_summary', 'risk_trend']);
        }

        // Ensure 'outlook' is a string
        if (empty($result['outlook']) || !is_string($result['outlook'])) {
            $result['outlook'] = $this->pickString($result, ['outlook', 'forward_outlook', 'trend_analysis']);
        }

        return $result;
    }

    private function isStringList(mixed $val): bool
    {
        return is_array($val) && !empty($val) && array_is_list($val) && is_string($val[0]);
    }

    /**
     * Extract human-readable concern strings from structured issue arrays.
     * Handles the coder model's typical output: [{ directorate, kpi, issue }, ...]
     */
    private function extractIssueStrings(array $data, array $keys): array
    {
        $out = [];
        foreach ($keys as $key) {
            if (!isset($data[$key]) || !is_array($data[$key])) continue;
            foreach ($data[$key] as $item) {
                if (is_string($item)) {
                    $out[] = $item;
                } elseif (is_array($item)) {
                    $dir = $item['directorate'] ?? '';
                    $kpi = $item['kpi'] ?? '';
                    $issue = $item['issue'] ?? $item['description'] ?? '';
                    if ($issue) {
                        $label = implode(' — ', array_filter([$dir, $kpi]));
                        $out[] = $label ? "{$label}: {$issue}" : $issue;
                    }
                }
            }
        }
        return $out;
    }

    /**
     * Pick the first string value from $data matching one of the candidate keys.
     * If the value is an array/object, convert it to a readable string.
     */
    private function pickString(array $data, array $keys): ?string
    {
        foreach ($keys as $key) {
            if (!isset($data[$key])) continue;
            $v = $data[$key];
            if (is_string($v) && $v !== '') return $v;
            if (is_array($v)) {
                // If it's a list of strings, join them
                if (array_is_list($v)) {
                    $joined = implode(' ', array_filter($v, 'is_string'));
                    if ($joined !== '') return $joined;
                }
                // Associative array — stringify key-value pairs
                $parts = [];
                foreach ($v as $k => $val) {
                    if (is_array($val)) {
                        $parts[] = ucfirst(str_replace('_', ' ', $k)) . ': ' . implode(', ', array_filter($val, 'is_string'));
                    } else {
                        $parts[] = ucfirst(str_replace('_', ' ', $k)) . ': ' . $val;
                    }
                }
                $joined = implode('. ', $parts);
                if ($joined !== '') return $joined;
            }
        }
        return null;
    }

    /**
     * Pick the first array-of-strings value from $data matching one of the candidate keys.
     */
    private function pickStringArray(array $data, array $keys): array
    {
        foreach ($keys as $key) {
            if (!isset($data[$key]) || !is_array($data[$key])) continue;
            $v = $data[$key];
            if (array_is_list($v) && !empty($v)) {
                return array_values(array_filter($v, 'is_string'));
            }
            // Associative array — try extracting values or keys
            if (!empty($v)) {
                $strings = [];
                foreach ($v as $k => $val) {
                    if (is_string($val)) {
                        $strings[] = $val;
                    } elseif (is_array($val)) {
                        foreach ($val as $item) {
                            if (is_string($item)) $strings[] = $item;
                        }
                    }
                }
                if (!empty($strings)) return $strings;
            }
        }
        return [];
    }

    private function gatherExecutiveContext(): array
    {
        $summary = $this->dashboard->getExecutiveSummary();
        $directorates = Directorate::active()->with(['kpis' => fn($q) => $q->active()])->get();

        $alerts = Alert::unread()
            ->orderByDesc('created_at')
            ->limit(20)
            ->get(['type', 'severity', 'title', 'message', 'created_at'])
            ->toArray();

        $kpiSummaries = [];
        foreach ($directorates as $dir) {
            $dirKpis = [];
            foreach ($dir->kpis as $kpi) {
                $latest = KpiEntry::where('kpi_id', $kpi->id)
                    ->where('directorate_id', $dir->id)
                    ->orderByDesc('period_date')
                    ->first();

                if ($latest) {
                    $dirKpis[] = [
                        'name' => $kpi->name,
                        'category' => $kpi->category,
                        'value' => $latest->value,
                        'target' => $kpi->target_value,
                        'status' => $kpi->getStatusForValue($latest->value),
                        'change_pct' => $latest->getChangePercentage(),
                        'unit' => $kpi->unit,
                        'deadline' => $kpi->target_deadline?->format('Y-m-d'),
                        'days_to_deadline' => $kpi->daysUntilDeadline(),
                    ];
                }
            }

            $kpiSummaries[] = [
                'directorate' => $dir->name,
                'kpis' => $dirKpis,
            ];
        }

        return [
            'organization_summary' => $summary,
            'directorates' => $kpiSummaries,
            'recent_alerts' => $alerts,
            'wayleave_and_survey_data' => $this->gatherWayleaveContext(),
            'analysis_date' => now()->format('Y-m-d'),
        ];
    }

    private function gatherAnomalyContext(int $kpiId, int $directorateId): array
    {
        $kpi = Kpi::find($kpiId);
        $directorate = Directorate::find($directorateId);

        // Get recent entries for this KPI
        $entries = KpiEntry::where('kpi_id', $kpiId)
            ->where('directorate_id', $directorateId)
            ->orderByDesc('period_date')
            ->limit(12)
            ->get(['value', 'previous_value', 'period_date', 'notes'])
            ->toArray();

        // Get other KPIs for the same directorate (for correlation)
        $otherKpis = [];
        $dirKpis = Kpi::whereHas('directorates', fn($q) => $q->where('directorates.id', $directorateId))
            ->where('id', '!=', $kpiId)
            ->active()
            ->get();

        foreach ($dirKpis as $otherKpi) {
            $latest = KpiEntry::where('kpi_id', $otherKpi->id)
                ->where('directorate_id', $directorateId)
                ->orderByDesc('period_date')
                ->first();

            if ($latest) {
                $otherKpis[] = [
                    'name' => $otherKpi->name,
                    'value' => $latest->value,
                    'change_pct' => $latest->getChangePercentage(),
                    'status' => $otherKpi->getStatusForValue($latest->value),
                ];
            }
        }

        // Get recent incidents/risks for this directorate
        $incidents = [];
        if (class_exists(\App\Models\Incident::class)) {
            $incidents = Incident::where('directorate_id', $directorateId)
                ->where('created_at', '>=', now()->subMonths(3))
                ->orderByDesc('created_at')
                ->limit(10)
                ->get(['title', 'severity', 'status', 'created_at'])
                ->toArray();
        }

        $risks = Risk::where('directorate_id', $directorateId)
            ->orderByDesc('score')
            ->limit(10)
            ->get(['title', 'category', 'score', 'status'])
            ->toArray();

        return [
            'anomalous_kpi' => [
                'name' => $kpi?->name,
                'category' => $kpi?->category,
                'unit' => $kpi?->unit,
                'target' => $kpi?->target_value,
                'trend_direction' => $kpi?->trend_direction,
            ],
            'directorate' => $directorate?->name,
            'recent_entries' => $entries,
            'other_directorate_kpis' => $otherKpis,
            'recent_incidents' => $incidents,
            'active_risks' => $risks,
        ];
    }

    private function gatherDirectorateContext(int $directorateId): array
    {
        $detail = $this->dashboard->getDirectorateDetail($directorateId);
        $directorate = Directorate::find($directorateId);

        // Get deadline info for KPIs
        $kpisWithDeadlines = Kpi::whereHas('directorates', fn($q) => $q->where('directorates.id', $directorateId))
            ->active()
            ->whereNotNull('target_deadline')
            ->get()
            ->map(fn($kpi) => [
                'name' => $kpi->name,
                'target' => $kpi->target_value,
                'deadline' => $kpi->target_deadline->format('Y-m-d'),
                'days_remaining' => $kpi->daysUntilDeadline(),
                'is_overdue' => $kpi->isOverdue(),
            ])
            ->toArray();

        return [
            'directorate_name' => $directorate?->name,
            'performance_data' => $detail,
            'kpis_with_deadlines' => $kpisWithDeadlines,
            'analysis_date' => now()->format('Y-m-d'),
        ];
    }

    private function gatherQueryContext(): array
    {
        $summary = $this->dashboard->getExecutiveSummary();

        $directorates = Directorate::active()->get(['id', 'name', 'code'])->toArray();

        $kpis = Kpi::active()->get(['id', 'name', 'code', 'category', 'unit', 'target_value'])->toArray();

        $recentAlerts = Alert::unread()
            ->orderByDesc('created_at')
            ->limit(10)
            ->get(['type', 'severity', 'title', 'message'])
            ->toArray();

        // Gather wayleave and survey data
        $wayleaveData = $this->gatherWayleaveContext();

        return [
            'organization_summary' => $summary,
            'directorates' => $directorates,
            'available_kpis' => $kpis,
            'recent_alerts' => $recentAlerts,
            'wayleave_and_survey_data' => $wayleaveData,
            'current_date' => now()->format('Y-m-d'),
        ];
    }

    private function gatherWeeklyContext(): array
    {
        $weekAgo = now()->subWeek();

        // Get entries from this week vs last week
        $thisWeekEntries = KpiEntry::where('period_date', '>=', $weekAgo)
            ->with(['kpi:id,name,category,unit,target_value', 'directorate:id,name'])
            ->get()
            ->groupBy('directorate.name')
            ->map(fn($entries) => $entries->map(fn($e) => [
                'kpi' => $e->kpi?->name,
                'value' => $e->value,
                'change_pct' => $e->getChangePercentage(),
                'status' => $e->kpi ? $e->kpi->getStatusForValue($e->value) : null,
            ]))
            ->toArray();

        $newAlerts = Alert::where('created_at', '>=', $weekAgo)
            ->orderByDesc('created_at')
            ->get(['type', 'severity', 'title', 'message', 'created_at'])
            ->toArray();

        return [
            'week_ending' => now()->format('Y-m-d'),
            'week_starting' => $weekAgo->format('Y-m-d'),
            'directorate_performance' => $thisWeekEntries,
            'alerts_this_week' => $newAlerts,
            'total_alerts' => count($newAlerts),
        ];
    }

    /**
     * Gather wayleave and survey processing data across all directorates.
     */
    private function gatherWayleaveContext(): array
    {
        $categories = ['wayleave', 'survey'];
        $result = [];

        foreach ($categories as $category) {
            // Get the latest report date for each directorate
            $latestEntries = WayleaveEntry::where('category', $category)
                ->selectRaw('directorate_id, MAX(report_date) as latest_date')
                ->groupBy('directorate_id')
                ->get();

            foreach ($latestEntries as $entry) {
                $directorate = Directorate::find($entry->directorate_id);
                if (!$directorate) continue;

                $rows = WayleaveEntry::where('directorate_id', $entry->directorate_id)
                    ->where('category', $category)
                    ->whereDate('report_date', $entry->latest_date)
                    ->orderBy('aspect')
                    ->get()
                    ->map(fn(WayleaveEntry $e) => [
                        'aspect' => $e->aspect,
                        'received' => (int) $e->received,
                        'cleared' => (int) $e->cleared,
                        'pending' => (int) $e->received - (int) $e->cleared,
                    ])
                    ->toArray();

                if (!empty($rows)) {
                    $totalReceived = array_sum(array_column($rows, 'received'));
                    $totalCleared = array_sum(array_column($rows, 'cleared'));

                    $result[] = [
                        'category' => $category,
                        'directorate' => $directorate->name,
                        'report_date' => $entry->latest_date,
                        'total_received' => $totalReceived,
                        'total_cleared' => $totalCleared,
                        'total_pending' => $totalReceived - $totalCleared,
                        'clearance_rate_pct' => $totalReceived > 0
                            ? round(($totalCleared / $totalReceived) * 100, 1)
                            : 0,
                        'breakdown_by_aspect' => $rows,
                    ];
                }
            }
        }

        return $result;
    }

    // ═══════════════════════════════════════════════════════════
    //  Cache Helpers
    // ═══════════════════════════════════════════════════════════

    private function getCache(string $key): ?array
    {
        try {
            return Cache::tags(['ai'])->get($key);
        } catch (\BadMethodCallException) {
            return Cache::get($key);
        }
    }

    private function setCache(string $key, array $value, int $minutes): void
    {
        try {
            Cache::tags(['ai'])->put($key, $value, now()->addMinutes($minutes));
        } catch (\BadMethodCallException) {
            Cache::put($key, $value, now()->addMinutes($minutes));
        }
    }

    /**
     * Clear all AI analysis caches (e.g., after new data import).
     */
    public function clearCache(): void
    {
        try {
            Cache::tags(['ai'])->flush();
        } catch (\BadMethodCallException) {
            // Fall back to individual key clearing
            Cache::forget('ai_executive_insights');
        }
    }
}
