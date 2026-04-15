# AI Service Setup Guide

This guide explains how to configure the AI service for the ZESCO Executive Dashboard on any environment —  local development (Windows/WAMP), test, or production (Oracle Linux 8 / Nginx).

---

## 1. Overview

The dashboard uses a **provider-agnostic AI layer** that supports:

- **Groq** (cloud, free tier) — recommended for speed and cost
- **OpenAI** (cloud, paid) — alternative cloud provider
- **Ollama** (local inference) — for fully offline/on-premise use

The AI powers: executive insights, natural language queries, anomaly explanations, recommendations, deadline breach predictions, and a PP-scoped assistant.

---

## 2. Environment Variables

Add the following to your `.env` file:

### Option A: Groq (Recommended — Free & Fast)

```env
AI_PROVIDER=openai
OPENAI_API_KEY=gsk_YOUR_GROQ_API_KEY_HERE
OPENAI_MODEL=llama-3.3-70b-versatile
OPENAI_BASE_URL=https://api.groq.com/openai/v1
```

> Groq uses an OpenAI-compatible API, so `AI_PROVIDER=openai` is correct.

Get a free API key at: https://console.groq.com

### Option B: OpenAI (Paid)

```env
AI_PROVIDER=openai
OPENAI_API_KEY=sk-YOUR_OPENAI_KEY_HERE
OPENAI_MODEL=gpt-4o-mini
OPENAI_BASE_URL=https://api.openai.com/v1
```

> Minimum $5 prepay required. `gpt-4o-mini` is fast and cheap.

### Option C: Ollama (Local / On-Premise)

```env
AI_PROVIDER=ollama
OLLAMA_URL=http://localhost:11434
OLLAMA_MODEL=qwen2.5:14b
OLLAMA_TIMEOUT=600
```

> Requires Ollama installed and running locally. Larger models (32b, 72b) need significant GPU VRAM.

---

## 3. Post-Configuration Steps

After updating `.env`, always run:

```bash
php artisan config:clear
```

If using a web server (Nginx + PHP-FPM), also restart PHP-FPM:

```bash
sudo systemctl restart php-fpm
```

---

## 4. Queue Worker Setup

AI requests can run asynchronously when the queue driver is not `sync`. The `.env` should have:

```env
QUEUE_CONNECTION=database
```

### Local Development

Run the queue worker manually:

```bash
php artisan queue:work
```

### Production (systemd service)

Create `/etc/systemd/system/laravel-queue-worker.service`:

```ini
[Unit]
Description=Laravel Queue Worker (ZESCO Dashboard)
After=network.target

[Service]
User=nginx
Group=nginx
WorkingDirectory=/path/to/zesco-dashboard
ExecStart=/usr/bin/php artisan queue:work database --sleep=3 --tries=3 --max-time=3600
Restart=always
RestartSec=5

[Install]
WantedBy=multi-user.target
```

Then enable it:

```bash
sudo systemctl daemon-reload
sudo systemctl enable --now laravel-queue-worker
```

> Adjust `User`, `Group`, and `WorkingDirectory` to match your server.

---

## 5. Common Issues & Solutions

### 5.1 "AI service unavailable" — SSL Certificate Error (Windows / WAMP)

**Symptom:** AI service reports unavailable. Laravel logs show:
```
cURL error 60: SSL certificate problem: unable to get local issuer certificate
```

**Cause:** WAMP's PHP has no CA certificate bundle configured.

**Fix:**

1. Download the Mozilla CA bundle:
   ```bash
   curl -o "C:\wamp64\bin\php\php8.x.x\extras\ssl\cacert.pem" https://curl.se/ca/cacert.pem
   ```

2. Edit `php.ini` (find it with `php -r "echo php_ini_loaded_file();"`) and update:
   ```ini
   curl.cainfo = "C:\wamp64\bin\php\php8.x.x\extras\ssl\cacert.pem"
   openssl.cafile = "C:\wamp64\bin\php\php8.x.x\extras\ssl\cacert.pem"
   ```

3. Restart WAMP (Apache).

---

### 5.2 "AI service unavailable" — IPv6 Resolution Failure (Linux / Oracle Linux)

**Symptom:** `curl` works from the command line but PHP-FPM cannot reach the API.

**Cause:** PHP's cURL tries IPv6 first, which fails on some server configurations.

**Fix:** The `OpenAiProvider` includes `force_ip_resolve => 'v4'` in the HTTP client to force IPv4. Ensure you have the latest version of `app/Services/AI/OpenAiProvider.php` deployed.

---

### 5.3 "AI service unavailable" — SELinux Blocking Outbound Connections (Oracle Linux / RHEL)

**Symptom:** PHP CLI works fine, but requests through Nginx + PHP-FPM fail silently.

**Cause:** SELinux blocks httpd/PHP-FPM from making outbound network connections by default.

**Fix:**

```bash
sudo setsebool -P httpd_can_network_connect 1
sudo systemctl restart php-fpm
```

Verify:
```bash
getsebool httpd_can_network_connect
# Should show: httpd_can_network_connect --> on
```

---

### 5.4 "AI service unavailable" — Firewall Blocking Outbound HTTPS

**Symptom:** Both `curl` and PHP fail to reach the API.

**Cause:** Corporate firewall or security group blocks outbound traffic to external APIs.

**Fix:** Ask the network team to whitelist outbound HTTPS (port 443) to:
- `api.groq.com` (if using Groq)
- `api.openai.com` (if using OpenAI)

Test connectivity:
```bash
curl -s -o /dev/null -w "%{http_code}" https://api.groq.com/openai/v1/models \
  -H "Authorization: Bearer YOUR_API_KEY"
```

A `200` response means the connection works.

---

### 5.5 Rate Limiting (Groq Free Tier)

**Symptom:** AI requests intermittently fail with HTTP 429 errors.

**Cause:** Groq free tier limits for `llama-3.3-70b-versatile`:
- 30 requests/minute
- 14,400 requests/day
- ~6,000 tokens/minute

**Impact:** Fine for single-user testing. Multiple concurrent users will hit limits.

**Mitigation:**
- The dashboard caches AI responses (24h for insights, 12h for anomalies/recommendations). Cached results don't consume API calls.
- For production with many users, consider Groq's paid tier, OpenAI, or hosting Ollama on-premise.
- Use a smaller/faster model like `llama-3.1-8b-instant` for higher rate limits during testing.

---

### 5.6 Missing PHP Extensions

**Symptom:** Various HTTP/SSL errors.

**Fix:** Ensure `curl` and `openssl` PHP extensions are installed:

```bash
# Check
php -m | grep -E "curl|openssl"

# Install (Oracle Linux / RHEL)
sudo dnf install php-curl php-openssl
sudo systemctl restart php-fpm
```

---

## 6. Diagnostic Commands

Use these to troubleshoot AI issues on any server:

```bash
# 1. Check what Laravel sees
php artisan tinker --execute="
echo 'provider=' . config('dashboard.ai.provider') . PHP_EOL;
echo 'enabled=' . (config('dashboard.ai.enabled') ? 'true' : 'false') . PHP_EOL;
echo 'key=' . (empty(config('dashboard.ai.openai.key')) ? 'EMPTY' : 'set') . PHP_EOL;
echo 'url=' . config('dashboard.ai.openai.url') . PHP_EOL;
"

# 2. Test the actual HTTP call from PHP
php artisan tinker --execute="
try {
    \$key = config('dashboard.ai.openai.key');
    \$url = config('dashboard.ai.openai.url');
    \$r = Illuminate\Support\Facades\Http::timeout(10)
        ->withHeaders(['Authorization' => 'Bearer ' . \$key])
        ->get(\$url . '/models');
    echo 'status=' . \$r->status() . PHP_EOL;
} catch (\Exception \$e) {
    echo 'ERROR: ' . \$e->getMessage() . PHP_EOL;
}
"

# 3. Test full provider availability
php artisan tinker --execute="
\$p = app(App\Services\AI\AiProviderManager::class);
echo 'available=' . (\$p->isAvailable() ? 'true' : 'false') . PHP_EOL;
echo 'identifier=' . \$p->getIdentifier() . PHP_EOL;
"

# 4. Check SELinux (Linux only)
getenforce
getsebool httpd_can_network_connect

# 5. Check Laravel logs
tail -50 storage/logs/laravel.log | grep -i "ai\|openai\|groq\|curl\|ssl"

# 6. Check PHP-FPM logs (Linux only)
sudo tail -50 /var/log/php-fpm/www-error.log
```

---

## 7. Performance Tips

| Action | Impact | Effort |
|---|---|---|
| Use Groq or OpenAI instead of local Ollama | 10-20x faster responses | `.env` change |
| Use a smaller model (`llama-3.1-8b-instant`, `gpt-4o-mini`) | 2-5x faster | `.env` change |
| Leverage caching (already built-in) | Instant on cache hit | None |
| Add a vector DB for smarter context retrieval | 2-4x faster queries | Medium |
| Pre-summarize dashboard data for AI context | 1.5-2x faster | Low-medium |
