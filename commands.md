
Based on my analysis of your ZESCO Dashboard project, here's a comprehensive list of all commands you need to run:

## **Essential Services (Must Run)**

### 1. **Laravel Web Server**

```
php artisan serve
```

*Default: [http://localhost:8000](vscode-file://vscode-app/c:/Program%20Files/Microsoft%20VS%20Code/072586267e/resources/app/out/vs/code/electron-browser/workbench/workbench.html)*

### 2. **Vite Development Server** (Frontend Assets)

```
npm run dev
```

*Hot module replacement for Vue 3 components*

### 3. **Queue Worker** (Background Jobs)

```
php artisan queue:work --tries=3
```

*Processes:*

* `ProcessAiRequest` - AI analysis requests (15 min timeout)
* `SendWhatsAppTemplateMessage` - WhatsApp notifications

**Alternative queue commands:**

```
# Watch for code changes and restart
php artisan queue:work --tries=3 --timeout=900

# Specific queue
php artisan queue:work --queue=default,ai,notifications

# Stop after processing all jobs
php artisan queue:work --stop-when-empty
```

### 4. **Laravel Reverb** (WebSocket Server)

```
php artisan reverb:start
```

*Default: ws://localhost:8080*
*Enables real-time dashboard updates*

### 5. **Task Scheduler** (Cron Jobs)

```
# Development - keeps running
php artisan schedule:work

# Production - run via cron every minute
php artisan schedule:run
```

**Scheduled tasks:**

* `dashboard:simulate` - Every 5 minutes
* `dashboard:check-alerts` - Every 10 minutes
* `dashboard:weekly-digest` - Mondays at 7:00 AM
* `cache:prune-stale-tags` - Daily

---

## **AI Services (If Using AI Features)**

### 6. **Ollama Server** (Local AI - Default)

```
# Start Ollama service
ollama serve

# Pull required models
ollama pull qwen2.5:72b
ollama pull qwen2.5:14b
```

*Default: [http://localhost:11434](vscode-file://vscode-app/c:/Program%20Files/Microsoft%20VS%20Code/072586267e/resources/app/out/vs/code/electron-browser/workbench/workbench.html)*

**Alternative: OpenAI**

* Set `AI_PROVIDER=openai` in [.env](vscode-file://vscode-app/c:/Program%20Files/Microsoft%20VS%20Code/072586267e/resources/app/out/vs/code/electron-browser/workbench/workbench.html)
* Add `OPENAI_API_KEY` to [.env](vscode-file://vscode-app/c:/Program%20Files/Microsoft%20VS%20Code/072586267e/resources/app/out/vs/code/electron-browser/workbench/workbench.html)
* No local server needed

---

## **Manual Artisan Commands**

### **Data Simulation**

```
# Run single simulation cycle
php artisan dashboard:simulate

# Generate 6 months of historical data
php artisan dashboard:simulate --seed --months=6
```

### **Alert Checks**

```
# Run all alert checks
php artisan dashboard:check-alerts

# Specific alert types
php artisan dashboard:check-alerts --type=thresholds
php artisan dashboard:check-alerts --type=deadlines
php artisan dashboard:check-alerts --type=stale
php artisan dashboard:check-alerts --type=milestones
```

### **Weekly Digest**

```
php artisan dashboard:weekly-digest
```

### **Database Operations**

```
# Run migrations
php artisan migrate

# Seed database
php artisan db:seed

# Fresh migration with seeding
php artisan migrate:fresh --seed
```

### **Cache Management**

```
# Clear all caches
php artisan cache:clear
php artisan config:clear
php artisan view:clear
php artisan route:clear

# Production optimization
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

---

## **Complete Development Setup**

**Run these 5 commands in separate terminals:**

```
# Terminal 1 - Web Server
php artisan serve

# Terminal 2 - Frontend Dev Server
npm run dev

# Terminal 3 - Queue Worker
php artisan queue:work --tries=3 --timeout=900

# Terminal 4 - WebSocket Server
php artisan reverb:start

# Terminal 5 - Task Scheduler
php artisan schedule:work
```

**Optional Terminal 6 - Ollama (if using local AI):**

```
ollama serve
```

---

## **Production Build**

```
# Build frontend for production
npm run build

# Optimize Laravel
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Run migrations
php artisan migrate --force
```

---

## **Quick Reference**

| Service   | Command                       | Port  | Purpose         |
| --------- | ----------------------------- | ----- | --------------- |
| Laravel   | `php artisan serve`         | 8000  | Web application |
| Vite      | `npm run dev`               | 5173  | Frontend HMR    |
| Reverb    | `php artisan reverb:start`  | 8080  | WebSocket       |
| Queue     | `php artisan queue:work`    | -     | Background jobs |
| Scheduler | `php artisan schedule:work` | -     | Cron tasks      |
| Ollama    | `ollama serve`              | 11434 | Local AI        |
