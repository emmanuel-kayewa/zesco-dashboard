export function useAiTasks() {
    /**
     * Build CSRF headers, preferring the XSRF-TOKEN cookie (refreshed by
     * Laravel on every response) over the meta tag (only set at page load).
     */
    function csrfHeaders() {
        const cookie = document.cookie.match(/XSRF-TOKEN=([^;]+)/);
        if (cookie) return { 'X-XSRF-TOKEN': decodeURIComponent(cookie[1]) };
        const meta = document.querySelector('meta[name="csrf-token"]')?.content;
        return meta ? { 'X-CSRF-TOKEN': meta } : {};
    }

    /** Make a lightweight GET so Laravel sets a fresh XSRF-TOKEN cookie. */
    async function refreshCsrf() {
        try {
            await fetch('/', { credentials: 'same-origin', headers: { 'Accept': 'text/html' } });
        } catch { /* swallow – the retry will surface the real error */ }
    }

    async function pollTask(taskId, intervalMs = 3000, maxPollMs = 900000) {
        const deadline = Date.now() + maxPollMs;

        while (Date.now() < deadline) {
            await new Promise(r => setTimeout(r, intervalMs));

            const resp = await fetch(`/api/ai/task/${taskId}`, {
                headers: {
                    'Accept': 'application/json',
                    ...csrfHeaders(),
                },
            });

            if (!resp.ok) {
                const text = await resp.text();
                let message = `Polling failed (HTTP ${resp.status})`;
                try { message = JSON.parse(text).message || message; } catch {}
                throw new Error(message);
            }

            const data = await resp.json();

            if (data.status === 'completed') return data.result;
            if (data.status === 'failed') throw new Error(data.error || 'AI processing failed');
            // else: queued / running — keep polling
        }

        throw new Error('AI request timed out');
    }

    /**
     * Post to an AI endpoint. Handles both sync and async (job) responses.
     * If the response contains { async: true, task_id }, it polls until completion.
     * On a 419 (CSRF mismatch), refreshes the token cookie and retries once.
     */
    async function aiPost(url, body = {}, _retried = false) {
        const resp = await fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                ...csrfHeaders(),
            },
            body: JSON.stringify(body),
        });

        // On CSRF mismatch, refresh the token cookie and retry once
        if (resp.status === 419 && !_retried) {
            await refreshCsrf();
            return aiPost(url, body, true);
        }

        // Guard: read as text first so an HTML error page doesn't blow up JSON.parse
        const text = await resp.text();

        let data;
        try {
            data = JSON.parse(text);
        } catch {
            console.error('Non-JSON response from', url, ':', text.substring(0, 300));
            throw new Error(
                resp.status === 419
                    ? 'Session expired — please refresh the page.'
                    : `Server returned an unexpected response (HTTP ${resp.status}). Please try again.`
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

    return {
        aiPost,
        pollTask,
    };
}
