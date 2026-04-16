# ZESCO Executive Insights Dashboard Platform

A secure, executive-grade web application for ZESCO Limited that delivers organization-wide performance insights across 12 directorates — featuring AI-powered analytics, real-time WebSocket updates, Planning & Projects portfolio management, and multi-channel notifications.

## Tech Stack

| Layer         | Technology                                  |
| ------------- | ------------------------------------------- |
| Backend       | Laravel 12 (PHP 8.2+)                       |
| Frontend      | Vue 3.5 + Inertia.js v2                     |
| Charts        | Apache ECharts 5.5 + Highcharts 12          |
| Styling       | Tailwind CSS 3.4 + Flowbite 4               |
| State         | Pinia 2.2                                   |
| Auth          | Azure AD OAuth (Socialite) + Magic Link     |
| AI            | Ollama (Qwen 2.5) / OpenAI (GPT-4)          |
| WebSocket     | Laravel Reverb + Echo                       |
| Notifications | Email (Postmark) + WhatsApp Cloud API       |
| Build         | Vite 6                                      |
| Database      | MySQL (current) → Oracle via OCI8 (future) |
| Export        | DomPDF + Maatwebsite Excel                  |

## Architecture

```
┌──────────────────────────────────────────────────────┐
│                     Vue 3 SPA                        │
│  Pages → Components → Charts → Composables → Pinia  │
├──────────────────────────────────────────────────────┤
│                Inertia.js v2 Bridge                  │
├──────────────────────────────────────────────────────┤
│              Laravel Controllers (15+)               │
│  Dashboard │ PP │ DataEntry │ AI │ Admin │ Export     │
├──────────────────────────────────────────────────────┤
│                  Service Layer                       │
│  DashboardService │ PpDashboardService │ AlertService│
│  AiAnalysisService │ SimulationService │ WhatsApp    │
├──────────────────────────────────────────────────────┤
│            DataSource Abstraction                    │
│  SimulationDataSource ←→ ManualInputDataSource       │
│                    ↓                                 │
│           OracleDataSource (future)                  │
├──────────────────────────────────────────────────────┤
│          AI Provider Abstraction                     │
│  OllamaProvider ←→ OpenAiProvider                    │
├──────────────────────────────────────────────────────┤
│          Eloquent Models (27) + DB                   │
└──────────────────────────────────────────────────────┘
```

### DataSource Pattern

The `DataSourceManager` uses a strategy pattern (`DataSourceInterface`) to seamlessly switch between data sources:

1. **SimulationDataSource** — Generates realistic mock data with seeded trends (default for development)
2. **ManualInputDataSource** — Reads from manual data entry tables
3. **OracleDataSource** — Connects to ZESCO's Oracle ERP (future implementation)

Switch via `DASHBOARD_DATA_SOURCE=simulation|manual|oracle` in `.env`.

### AI Provider Pattern

The `AiProviderManager` abstracts LLM interaction behind `AiProviderInterface`:

1. **OllamaProvider** — Local inference via Ollama (default: Qwen 2.5 72B, fast model: 14B)
2. **OpenAiProvider** — Cloud inference via OpenAI API (GPT-4)

Long-running AI requests are queued via `ProcessAiRequest` jobs and polled asynchronously from the frontend.

## Key Features

### Executive Dashboard

- KPI scorecard cards with colour-coded RAG status
- Trend line charts, bar charts, pie charts, gauge charts, and heatmaps
- Zambia geographic map visualization
- Cross-directorate comparison tables and charts
- Date range filtering and drill-down navigation

### Directorate Drill-down

- Per-directorate KPI trends, financials, projects, and risks
- Wayleave tracking module
- Incident management

### Planning & Projects (PP) Portfolio Management

- **Project tracking** — Budget, progress, stage, commissioned MW, REMS/PMO columns
- **Milestone management** — Planned vs actual dates with PMO tracking
- **Financial tracking** — Committed, disbursed, and paid amounts per project
- **Risk & issue register** — Severity scoring, mitigation plans, issue tracking
- **Safeguards & compliance** — Environmental and social safeguard monitoring
- **Programme outputs** — Deliverable tracking per project
- **Grid impact studies** — Technical impact assessments
- **Workstreams** — Cross-project work package management
- **Interactive dashboard** — Overview, explorer, grid studies, and project detail views
- **Weekly reports** — CRUD with section-based authoring, net metering entries, and project entries
- **Bulk import** — CSV/Excel upload with template downloads for all PP entities

### AI Insights

- Executive summary generation across all directorates
- Anomaly detection and explanation
- KPI breach prediction
- Natural language querying of dashboard and PP data
- AI-assisted KPI categorization during import
- Response caching with configurable TTLs

### Notifications & Alerts

- Configurable KPI drop threshold (default: 10%) and risk threshold (default: 7)
- Email alerts via `KpiAlertMail`
- WhatsApp Cloud API template messages via `SendWhatsAppTemplateMessage` job
- Weekly digest emails via `WeeklyDigestMail` (scheduled command)
- Real-time browser notifications via Laravel Reverb WebSocket

### Data Entry

- CRUD forms for KPIs, financials, projects, risks, incidents, and wayleaves
- Excel/CSV import with AI enrichment for KPIs
- Downloadable import templates
- Complete audit trail via `spatie/laravel-activitylog`

### Admin Panel

- User management (create, update, delete) with role assignment
- Directorate management
- Simulation engine toggle and manual trigger
- System settings configuration
- Audit log viewer with filtering
- KPI bulk import with AI-powered categorization

### Export & Reporting

- Executive summary PDF (landscape, company-branded)
- Executive summary Excel
- Per-directorate PDF and Excel exports
- PP data export command

### UX

- Dark mode with system-preference detection and manual toggle
- Print-optimized stylesheets for executive reports
- Responsive layout with collapsible sidebar
- Session timeout with auto-logout
- Role-based menu visibility

## Quick Start

```bash
# 1. Clone and install
git clone <repo-url> zesco-dashboard
cd zesco-dashboard
composer install
npm install

# 2. Configure environment
cp .env.example .env
php artisan key:generate

# 3. Database
php artisan migrate --seed

# 4. Build frontend & start dev server
npm run dev

# 5. Start Laravel server
php artisan serve

# 6. (Optional) Start queue worker for AI jobs
php artisan queue:work

# 7. (Optional) Start WebSocket server
php artisan reverb:start
```

## Environment Configuration

Copy `.env.example` and configure:

```env
# ── Database ────────────────────────────────────
DB_CONNECTION=mysql
DB_DATABASE=zesco_dashboard

# ── Azure AD (production) ──────────────────────
AZURE_AD_CLIENT_ID=your-client-id
AZURE_AD_CLIENT_SECRET=your-client-secret
AZURE_AD_TENANT_ID=your-tenant-id
AZURE_AD_REDIRECT_URI=/auth/azure/callback

# ── Dashboard ──────────────────────────────────
DASHBOARD_DATA_SOURCE=simulation        # simulation | manual | oracle
DASHBOARD_SIMULATION_ENABLED=true
DASHBOARD_SIMULATION_INTERVAL=30
DASHBOARD_ALLOWED_EMAIL_DOMAIN=zesco.co.zm

# ── AI Configuration ──────────────────────────
AI_ENABLED=true
AI_PROVIDER=ollama                      # ollama | openai
OLLAMA_URL=http://localhost:11434
OLLAMA_MODEL=qwen2.5:72b
OLLAMA_FAST_MODEL=qwen2.5:14b
OLLAMA_TIMEOUT=600

# OpenAI (alternative)
# OPENAI_API_KEY=sk-...
# OPENAI_MODEL=gpt-4

# ── WhatsApp Notifications (optional) ─────────
DASHBOARD_WHATSAPP_NOTIFICATIONS=false
WHATSAPP_CLOUD_API_TOKEN=your-token
WHATSAPP_PHONE_NUMBER_ID=your-phone-id
WHATSAPP_BUSINESS_ACCOUNT_ID=your-account-id

# ── Oracle (future) ───────────────────────────
# DB_ORACLE_SCHEMA=ZESCO
# ORACLE_HOST=oracle-server.zesco.co.zm
# ORACLE_PORT=1521
```

## Directory Structure

```
app/
├── Console/Commands/          # SimulateData, CheckAlerts, WeeklyDigest, ExportPpData
├── Events/                    # SimulationDataUpdated (WebSocket broadcast)
├── Exports/                   # ExecutiveSummaryExport, DirectorateDetailExport
├── Http/
│   ├── Controllers/           # 15+ controllers (Dashboard, PP, AI, Admin, Auth, Export…)
│   ├── Middleware/             # RoleMiddleware, SessionTimeout, DevAutoLogin, HandleInertiaRequests
│   └── Requests/              # Form validation request classes
├── Imports/                   # KpiImport, PpImport (Maatwebsite Excel)
├── Jobs/                      # ProcessAiRequest, SendWhatsAppTemplateMessage
├── Mail/                      # KpiAlertMail, WeeklyDigestMail
├── Models/                    # 27 Eloquent models
├── Providers/                 # Service providers
└── Services/
    ├── AI/                    # AiProviderInterface, AiProviderManager, Ollama, OpenAI
    ├── DataSources/           # DataSourceInterface, Simulation, ManualInput, Oracle
    ├── AiAnalysisService.php  # AI analysis orchestration with caching
    ├── AlertService.php       # Threshold monitoring and alert creation
    ├── DashboardService.php   # Executive dashboard data aggregation
    ├── DataSourceManager.php  # Strategy pattern data source switching
    ├── PpDashboardService.php # Power Projects dashboard logic
    ├── SimulationService.php  # Realistic data generation engine
    └── WhatsAppCloudService.php

resources/js/
├── Pages/
│   ├── Auth/                  # Login.vue
│   ├── Dashboard/             # Index, DirectorateDetail, Comparison, Wayleaves
│   ├── DataEntry/             # KpiEntry, FinancialEntry, ProjectEntry, RiskEntry,
│   │                          #   IncidentEntry, WayleaveEntry
│   ├── PP/
│   │   ├── Dashboard/         # Overview, Explorer, GridStudies, ProjectDetail
│   │   ├── Tabs/              # Projects, Milestones, Financials, Risks, Safeguards,
│   │   │                      #   ProgrammeOutputs, GridImpactStudies, Workstreams
│   │   └── WeeklyReport/      # Index, Create, Edit, Show
│   ├── AI/                    # Insights.vue
│   ├── Admin/                 # Index, AuditLogs, KpiImport
│   └── ComponentShowcase.vue  # Dev-only component gallery
├── Components/
│   ├── AI/                    # AiDrawer, AiChart, InsightsSummaryContent
│   ├── Charts/                # BaseChart, Line, Bar, Pie, Pie3D, Gauge,
│   │                          #   Heatmap, RadialProgress, ZambiaMap
│   ├── Dashboard/             # KpiCard
│   ├── Layout/                # AppLayout, DirectorateSidebar, SidebarGroup, SidebarLink
│   ├── Pp/                    # WeeklyReportCard
│   ├── PpImportModal.vue
│   └── UI/                    # Badge, Breadcrumb, Button, Card, ChartCard, ChartModal,
│                              #   DataTable, DatePicker, DateRangePicker, Dropdown, Input,
│                              #   Modal, PageHeader, PalettePicker, PillTabs,
│                              #   ProjectStackedList, SectionTabs, Select, TabCard, Textarea
└── Composables/               # useAiTasks, useBadges, useChartPalette, useChartPalettes,
                               #   useDarkMode, useEcho, useFormatters, useReportUpload
```

## Database Schema

35 migrations covering:

| Domain            | Tables                                                                                                                                                           |
| ----------------- | ---------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Auth & Org        | `roles`, `directorates`, `users`, `settings`                                                                                                             |
| KPIs              | `kpis`, `kpi_entries` (with deadline and code fields)                                                                                                        |
| Financials        | `financial_entries`                                                                                                                                            |
| Projects & Risks  | `projects`, `risks`, `incidents`, `wayleave_entries`                                                                                                     |
| Power Projects    | `pp_projects`, `pp_milestones`, `pp_financials`, `pp_risks`, `pp_safeguards`, `pp_programme_outputs`, `pp_grid_impact_studies`, `pp_workstreams` |
| PP Weekly Reports | `pp_weekly_reports`, `pp_report_sections`, `pp_report_project_entries`, `pp_report_net_metering_entries`                                                 |
| System            | `executive_notes`, `alerts`, `audit_logs`, `simulation_logs`, `jobs`, `failed_jobs`                                                                  |

## Models (27)

| Model                        | Purpose                                                   |
| ---------------------------- | --------------------------------------------------------- |
| `User`                     | Auth user with role, directorate, WhatsApp fields         |
| `Role`                     | RBAC roles (executive, directorate_head, admin)           |
| `Directorate`              | Organizational unit (12 directorates)                     |
| `Kpi`                      | KPI definition with category, unit, thresholds, deadlines |
| `KpiEntry`                 | Time-series KPI actuals and targets                       |
| `FinancialEntry`           | Budget vs actuals financial data                          |
| `Project`                  | General project tracking                                  |
| `Risk`                     | Enterprise risk register entries                          |
| `Incident`                 | Safety/operational incident records                       |
| `WayleaveEntry`            | Wayleave (land right) tracking                            |
| `PpProject`                | Power project with budget, stage, MW capacity             |
| `PpMilestone`              | Project milestone with planned/actual dates               |
| `PpFinancial`              | PP financial tracking (committed/disbursed/paid)          |
| `PpRisk`                   | PP risk with severity and mitigation                      |
| `PpSafeguard`              | Environmental/social safeguard compliance                 |
| `PpProgrammeOutput`        | Programme deliverable tracking                            |
| `PpGridImpactStudy`        | Grid technical impact assessment                          |
| `PpWorkstream`             | Cross-project work packages                               |
| `PpWeeklyReport`           | Weekly report container                                   |
| `PpReportSection`          | Report section content                                    |
| `PpReportProjectEntry`     | Per-project entry in weekly report                        |
| `PpReportNetMeteringEntry` | Net metering data in weekly report                        |
| `Alert`                    | System alert with read/dismiss state                      |
| `ExecutiveNote`            | Executive-authored notes                                  |
| `AuditLog`                 | Activity trail for all data changes                       |
| `SimulationLog`            | Simulation run history                                    |
| `Setting`                  | Key-value system settings                                 |

## Directorates (12)

| Code | Name                                   |
| ---- | -------------------------------------- |
| MD   | Managing Director's Office             |
| GEN  | Generation                             |
| T&S  | Transmission & Systems                 |
| DS   | Distribution & Supply                  |
| CS   | Customer Services                      |
| F&S  | Finance & Strategy                     |
| HR   | Human Resources & Administration       |
| ICT  | Information & Communication Technology |
| L&CS | Legal & Company Secretariat            |
| IA   | Internal Audit                         |
| P&E  | Projects & Engineering                 |
| SHE  | Safety, Health & Environment           |

## Roles & Permissions

| Role             | View Dashboard   | Data Entry | PP Module | Admin Panel |
| ---------------- | ---------------- | ---------- | --------- | ----------- |
| Executive        | All directorates | No         | View only | No          |
| Directorate Head | Own directorate  | Yes        | Full CRUD | No          |
| Admin            | All directorates | Yes        | Full CRUD | Yes         |

## Artisan Commands

```bash
# Run simulation cycle (generates realistic demo data)
php artisan dashboard:simulate

# Seed historical data (6 months of backfill)
php artisan dashboard:simulate --seed --months=6

# Check KPI drop and risk thresholds, create alerts
php artisan dashboard:check-alerts

# Send weekly digest email to executives
php artisan dashboard:weekly-digest

# Export PP data
php artisan pp:export-data
```

## Authentication

### Azure AD OAuth (Production)

1. Register app in Azure Portal → App registrations
2. Set redirect URI: `https://your-domain.com/auth/azure/callback`
3. Add API permissions: `User.Read`, `Directory.Read.All` (for group mapping)
4. Create client secret
5. Configure `.env` with tenant ID, client ID, client secret
6. Map Azure AD groups to roles in `AzureAdController@determineRole()`

### Magic Link (Fallback)

Available at `/auth/magic-link` for users without Azure AD access. Sends a one-time login link to a verified `@zesco.co.zm` email address.

### Development

In `local` environment, `DevAutoLogin` middleware bypasses authentication automatically.

## AI Integration

The AI module provides natural language analytics over dashboard data:

| Endpoint                            | Purpose                                      |
| ----------------------------------- | -------------------------------------------- |
| `POST /api/ai/executive-insights` | Generate cross-directorate executive summary |
| `POST /api/ai/explain-anomaly`    | Explain a detected KPI anomaly               |
| `POST /api/ai/recommendations`    | Generate actionable recommendations          |
| `POST /api/ai/query`              | Free-form natural language query             |
| `POST /api/ai/pp/query`           | Power Projects-specific query                |
| `POST /api/ai/predict-breach`     | Predict KPI threshold breaches               |
| `POST /api/ai/clear-cache`        | Clear cached AI responses                    |
| `GET /api/ai/task/{taskId}`       | Poll async AI job status                     |

### Setup (Ollama — recommended for on-premise)

```bash
# Install Ollama
curl -fsSL https://ollama.ai/install.sh | sh

# Pull models
ollama pull qwen2.5:72b    # Primary model
ollama pull qwen2.5:14b    # Fast model for lighter tasks

# Ensure .env has:
AI_ENABLED=true
AI_PROVIDER=ollama
OLLAMA_URL=http://localhost:11434
```

## Oracle Integration Guide

When ready to connect to ZESCO Oracle ERP:

1. Install Oracle Instant Client on the server
2. Enable the `oci8` PHP extension
3. Ensure `yajra/laravel-oci8` is installed (already in `composer.json`)
4. Update `.env`:
   ```env
   DASHBOARD_DATA_SOURCE=oracle
   ORACLE_HOST=oracle-server.zesco.co.zm
   ORACLE_PORT=1521
   ORACLE_DATABASE=ZESCODB
   ORACLE_USERNAME=dashboard_reader
   ORACLE_PASSWORD=secure-password
   DB_ORACLE_SCHEMA=ZESCO
   ```
5. Implement the TODO methods in `app/Services/DataSources/OracleDataSource.php`
6. Expected Oracle views/tables:
   - `ZESCO.VW_KPI_DATA` — KPI actuals and targets
   - `ZESCO.VW_FINANCIAL_DATA` — Budget and actuals
   - `ZESCO.VW_PROJECTS` — Project portfolio
   - `ZESCO.VW_RISKS` — Enterprise risk register

## Testing

```bash
# Unit tests
php artisan test --filter=Unit

# Feature tests
php artisan test --filter=Feature

# All tests
php artisan test
```

## Deployment

```bash
# 1. Install dependencies
composer install --no-dev --optimize-autoloader
npm ci

# 2. Production build
npm run build

# 3. Optimize Laravel
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan icons:cache

# 4. Run migrations
php artisan migrate --force

# 5. Seed data (first deploy only)
php artisan db:seed --force

# 6. Start queue worker (for AI jobs and WhatsApp messages)
php artisan queue:work --daemon

# 7. Start WebSocket server (for real-time updates)
php artisan reverb:start
```

### Production Checklist

- [ ] Set `APP_ENV=production` and `APP_DEBUG=false`
- [ ] Configure Azure AD credentials
- [ ] Set `DASHBOARD_DATA_SOURCE=manual` (or `oracle`)
- [ ] Configure mail driver for alert emails
- [ ] Set up supervisor for queue worker
- [ ] Set up supervisor for Reverb WebSocket server
- [ ] Configure SSL (see `SSL_SETUP_GUIDE.md`)
- [ ] Set `SESSION_LIFETIME` appropriately

## License

Proprietary — ZESCO Limited. All rights reserved.
