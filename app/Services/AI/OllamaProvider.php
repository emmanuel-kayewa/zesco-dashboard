<?php

namespace App\Services\AI;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OllamaProvider implements AiProviderInterface
{
    private string $baseUrl;
    private string $model;
    private int $timeout;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('dashboard.ai.ollama.url', 'http://localhost:11434'), '/');
        $this->model = config('dashboard.ai.ollama.model', 'qwen2.5:72b');
        $this->timeout = config('dashboard.ai.ollama.timeout', 600);
    }

    /**
     * Ensure the target model is loaded and any competing model is unloaded.
     * Ollama can crash or become unresponsive when swapping large models,
     * so we wait for stability after unloading before preloading.
     */
    private function ensureModelReady(string $model): void
    {
        try {
            $ps = Http::timeout(10)->get("{$this->baseUrl}/api/ps");
            if (!$ps->successful()) return;

            $loaded = collect($ps->json('models', []))->pluck('name')->toArray();

            // If our model is already loaded, nothing to do
            if (in_array($model, $loaded)) return;

            // Unload any other models first to free VRAM
            foreach ($loaded as $loadedModel) {
                if ($loadedModel !== $model) {
                    Log::info("Ollama: unloading {$loadedModel} to make room for {$model}");
                    Http::timeout(30)->post("{$this->baseUrl}/api/generate", [
                        'model' => $loadedModel,
                        'keep_alive' => 0,
                    ]);
                }
            }

            // Give Ollama time to release memory before loading a new model
            sleep(5);

            // Pre-load the target model — 32B+ models can take 3-5 minutes to load
            Log::info("Ollama: pre-loading {$model}");
            Http::timeout(300)->post("{$this->baseUrl}/api/generate", [
                'model' => $model,
                'keep_alive' => '10m',
            ]);
        } catch (\Exception $e) {
            Log::warning("Ollama model preload failed: {$e->getMessage()}");
            // Non-fatal — the actual chat call will still attempt to load
        }
    }

    /**
     * Wait for Ollama to become reachable, retrying a few times with backoff.
     * Returns true if Ollama is up, false if all retries failed.
     */
    private function waitForOllama(int $maxRetries = 5, int $baseDelaySec = 5): bool
    {
        for ($i = 0; $i < $maxRetries; $i++) {
            try {
                $response = Http::timeout(5)->get("{$this->baseUrl}/api/tags");
                if ($response->successful()) return true;
            } catch (\Exception) {
                // Ollama not ready yet
            }

            $delay = $baseDelaySec * ($i + 1);
            Log::info("Ollama: not reachable, retrying in {$delay}s (attempt " . ($i + 1) . "/{$maxRetries})");
            sleep($delay);
        }

        return false;
    }

    /**
     * Stream a chat response from Ollama, accumulating tokens into a single string.
     *
     * Using streaming prevents cURL "0 bytes received" timeouts because Ollama
     * sends tokens incrementally instead of buffering the entire response.
     * The read_timeout applies per-chunk, so long generations are fine as long
     * as tokens keep arriving.
     *
     * Will retry once on connection errors (e.g. after model swap).
     */
    private function streamChat(string $model, array $messages, array $ollamaOptions, ?string $format = null): string
    {
        $lastException = null;

        for ($attempt = 1; $attempt <= 2; $attempt++) {
            try {
                return $this->doStreamChat($model, $messages, $ollamaOptions, $format);
            } catch (\GuzzleHttp\Exception\ConnectException $e) {
                $lastException = $e;
                Log::warning("Ollama: connection failed on attempt {$attempt}, waiting for recovery", [
                    'error' => $e->getMessage(),
                ]);
                if ($attempt < 2 && $this->waitForOllama()) {
                    continue;
                }
            }
        }

        throw $lastException;
    }

    private function doStreamChat(string $model, array $messages, array $ollamaOptions, ?string $format = null): string
    {
        $payload = [
            'model' => $model,
            'messages' => $messages,
            'stream' => true,
            'options' => $ollamaOptions,
        ];

        if ($format) {
            $payload['format'] = $format;
        }

        $client = new Client([
            'base_uri' => $this->baseUrl,
            'connect_timeout' => 30,
            // read_timeout = max seconds to wait between chunks (not total time)
            'read_timeout' => $this->timeout,
            // No overall timeout — streaming can take as long as needed
            'timeout' => 0,
        ]);

        Log::info('Ollama: starting streamed chat', ['model' => $model, 'format' => $format ?? 'text']);

        $response = $client->post('/api/chat', [
            'json' => $payload,
            'stream' => true,
        ]);

        $body = $response->getBody();
        $content = '';
        $buffer = '';

        while (!$body->eof()) {
            $chunk = $body->read(8192);
            if ($chunk === '') {
                // Avoid busy-loop on empty reads
                usleep(10000); // 10ms
                continue;
            }
            $buffer .= $chunk;

            // Ollama sends newline-delimited JSON
            while (($newlinePos = strpos($buffer, "\n")) !== false) {
                $line = trim(substr($buffer, 0, $newlinePos));
                $buffer = substr($buffer, $newlinePos + 1);

                if ($line === '') continue;

                $json = json_decode($line, true);
                if (is_array($json)) {
                    $content .= $json['message']['content'] ?? '';
                    if (!empty($json['done'])) {
                        Log::info('Ollama: stream completed', [
                            'model' => $model,
                            'total_chars' => strlen($content),
                        ]);
                        return $content;
                    }
                }
            }
        }

        // In case we reached EOF without a done flag
        Log::warning('Ollama: stream ended without done flag', ['chars' => strlen($content)]);
        return $content;
    }

    public function chat(string $systemPrompt, string $userPrompt, array $options = []): string
    {
        try {
            // Disable PHP's execution time limit — large models can take minutes
            set_time_limit(0);

            $model = $options['model'] ?? $this->model;
            $this->ensureModelReady($model);

            // If Ollama crashed during model swap, wait for it to recover
            if (!$this->waitForOllama()) {
                Log::error('Ollama: service unreachable after retries');
                return '';
            }

            return $this->streamChat($model, [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => $userPrompt],
            ], [
                'temperature' => $options['temperature'] ?? 0.7,
                'num_predict' => $options['max_tokens'] ?? 2048,
            ]);
        } catch (\Exception $e) {
            Log::error('Ollama provider error', ['error' => $e->getMessage()]);
            return '';
        }
    }

    public function chatWithJson(string $systemPrompt, string $userPrompt, array $options = []): array
    {
        try {
            // Disable PHP's execution time limit — large models can take minutes
            set_time_limit(0);

            $model = $options['model'] ?? $this->model;
            $this->ensureModelReady($model);

            // If Ollama crashed during model swap, wait for it to recover
            if (!$this->waitForOllama()) {
                Log::error('Ollama: service unreachable after retries');
                return [];
            }

            $content = $this->streamChat($model, [
                ['role' => 'system', 'content' => $systemPrompt . "\n\nYou MUST respond with valid JSON only. No markdown, no explanation outside the JSON."],
                ['role' => 'user', 'content' => $userPrompt],
            ], [
                'temperature' => $options['temperature'] ?? 0.3,
                'num_predict' => $options['max_tokens'] ?? 4096,
            ], 'json');

            if (empty($content)) {
                Log::error('Ollama JSON API returned empty content');
                return [];
            }

            $decoded = json_decode($content, true);
            return is_array($decoded) ? $decoded : [];
        } catch (\Exception $e) {
            Log::error('Ollama JSON provider error', ['error' => $e->getMessage()]);
            return [];
        }
    }

    public function isAvailable(): bool
    {
        try {
            $response = Http::timeout(5)->get("{$this->baseUrl}/api/tags");
            return $response->successful();
        } catch (\Exception) {
            return false;
        }
    }

    public function getIdentifier(): string
    {
        return "ollama:{$this->model}";
    }
}
