# OnPageIQ MVP - Complete Specification

## Overview

OnPageIQ is a Laravel 12 SaaS application that allows users to submit URLs for comprehensive content analysis including spelling, grammar, SEO, and readability checks using OpenAI models. The app supports team collaboration, credit-based billing, and provides detailed reports with visual issue highlighting.

---

## Tech Stack

| Layer | Technology |
|-------|------------|
| Framework | Laravel 12 |
| Frontend | Livewire 4, Alpine.js, Tailwind CSS 4, Tailwind Plus (Simple Sidebar) |
| Admin Panel | Filament 5 |
| Database | SQLite (dev), PostgreSQL (prod) |
| Queue | Laravel Horizon |
| Real-time | Laravel Reverb (WebSockets) |
| Browser Automation | Playwright (configurable: local or external service) |
| AI | Laravel AI SDK - GPT-4o-mini (quick) / GPT-4o (deep analysis) |
| Payments | Stripe via Laravel Cashier |
| PDF Generation | Browsershot (Puppeteer) |
| Permissions | Spatie Laravel Permission (fine-grained RBAC) |
| Monitoring | Sentry + Laravel Pulse |
| Testing | Pest 4 |

---

## Authentication & Authorization

### Authentication Methods
- Email/password with email verification (Laravel Fortify)
- OAuth social login: Google, Microsoft 365
- Enterprise SSO: SAML 2.0 + OIDC (for Enterprise tier)

### Team/Organization Structure
```
Organization
├── Departments (sub-teams with own credit budgets)
│   └── Users (with roles)
└── Projects
    └── URLs
```

### RBAC Permissions (Fine-Grained)
System-level roles managed in Filament, team-level customization in main app.

**Permissions:**
- `create_project`, `edit_project`, `delete_project`, `view_project`
- `run_scan`, `view_reports`, `export_reports`
- `manage_team_members`, `manage_department_budgets`
- `view_billing`, `manage_billing`, `purchase_credits`
- `manage_api_keys`, `view_webhooks`, `manage_webhooks`
- `admin_all` (organization admin)

**Default Roles:**
- Owner: All permissions
- Admin: All except billing/delete org
- Manager: Department management + all project permissions
- Member: Run scans, view reports, export
- Viewer: View reports only

---

## Subscription & Credit System

### Subscription Tiers

| Tier | Monthly Price | Credits/Month | Projects | Team Size | Features |
|------|--------------|---------------|----------|-----------|----------|
| Free | $0 | 5 (one-time) | 1 | 1 | Spelling only, basic reports |
| Pro | TBD | TBD | Unlimited | 1 | All checks, PDF export, API access |
| Team | TBD | TBD | Unlimited | Up to 10 | + Team features, departments, priority queue |
| Enterprise | Custom | Custom | Unlimited | Unlimited | + SSO, dedicated support, custom contracts |

### Credit System Rules
- 1 page = 1 credit (standard scan)
- Large pages (50k+ words): 2-3 credits based on size (chunked analysis)
- Deep analysis (GPT-4o): 3x credit cost
- Re-checks: Full credit cost per check
- Subscription credits: Expire at billing cycle (one-time rollover allowed)
- Purchased credit packs: Never expire
- Grace overdraft: Allow small negative balance to complete in-progress scans

### Free Tier Abuse Prevention
- Track: IP address + email domain + browser fingerprint
- Block disposable email domains
- Prevent same IP from creating multiple free accounts
- Architecture ready for Castle fraud scoring integration later

---

## Core Features

### 1. Project Management
- Create projects with name, description, default language
- Configure checks per project: spelling, grammar, SEO, readability
- Plan-based defaults: Free=spelling only, Pro+=all checks
- User-specified expected language per project
- Duplicate URL handling: Detect and prompt to merge or add new

### 2. URL Management
- Add URLs to projects (single or bulk)
- Reachability check on add (HEAD request validation)
- HTML content only (reject PDF, images, non-HTML)
- URL status tracking: pending, scanning, completed, failed

### 3. Scanning Engine

**Content Extraction:**
- Full JavaScript rendering via Playwright
- Configurable browser infrastructure (local Playwright → external service)
- Extract full page content (headers, body, footer, navigation)
- Parallel scanning: Up to 5 URLs concurrently per project

**Analysis Pipeline:**
1. Queue scan job (priority based on subscription tier)
2. Render page with Playwright
3. Extract text content
4. Capture visual screenshots of DOM regions
5. Send to OpenAI for analysis (chunked if large)
6. Generate issue report with locations
7. Broadcast completion via Reverb WebSocket

**AI Analysis Tiers:**
- Quick scan: GPT-4o-mini (1 credit)
- Deep analysis: GPT-4o (3 credits) - toggle per scan

**Analysis Categories:**
- Spelling errors
- Grammar issues
- Readability score (Flesch-Kincaid, etc.)
- SEO suggestions (meta tags, headings, keyword density)
- Tone analysis

### 4. Reports

**Issue Display:**
- Text excerpt with highlighted issue
- Visual screenshot of element with issue overlay
- Severity rating (error, warning, suggestion)
- Category tags
- Suggested fix

**Views:**
- Full issue list (filterable by category, severity)
- Page-by-page breakdown
- Summary dashboard with scores

**Diff Feature:**
- Compare any two scans of same URL
- Full page text diff view
- Issue-focused diff view (show only around fixed/new issues)
- Side-by-side comparison

**Export:**
- PDF report (branded, via Browsershot)

**History:**
- Free tier: 30 days retention
- Paid tiers: Unlimited history

### 5. Dashboard

**Components:**
- Recent activity feed (last scans across projects)
- Project overview cards (health score, last scan, issue count)
- Analytics charts (issue trends, credit usage, team activity)
- Credit balance + subscription status
- Quick actions (new scan, new project)

---

## API & Webhooks

### REST API
Full parity with UI functionality:
- Authentication: API tokens (Sanctum)
- Projects: CRUD operations
- URLs: Add, remove, list
- Scans: Trigger, status, results
- Reports: Fetch, export
- Credits: Check balance

### Webhooks
- Events: `scan.started`, `scan.completed`, `scan.failed`, `credits.low`, `credits.depleted`
- Delivery: Retry with exponential backoff (3-5 attempts)
- Full tracking UI: Delivery status, manual retry, payload inspection

---

## UI/UX Specification

### Theme
- Tailwind Plus Simple Sidebar layout
- Blue enterprise color scheme (trust/reliability)
- True dark mode (Filament-style implementation)
- Responsive design

### Navigation Structure (Action-Centric)
```
Sidebar:
├── Dashboard
├── New Scan
├── Projects
├── Reports
├── Team (if team plan)
├── API & Webhooks
├── Settings
└── [Credit Balance Display]
```

### Real-Time Updates
- Scan progress: WebSocket via Laravel Reverb
- Show spinner with status messages during scan
- Instant report display on completion

### Notifications
- In-app toast notifications
- Email for: scan complete, credits low, credits depleted, team invites

---

## Filament Admin Panel

### Capabilities (Super-Admin)
- User management (CRUD, impersonation)
- Organization management
- Subscription/billing management
- View all scans and reports
- System analytics dashboard
- Feature flags management
- Plan/pricing configuration
- Email template management
- System settings
- Audit logs
- Role/permission management (system-level)

---

## Infrastructure & DevOps

### Browser Service Architecture
```php
// config/onpageiq.php
'browser' => [
    'driver' => env('BROWSER_DRIVER', 'local'), // local, browserless, custom
    'local' => [
        'executable_path' => env('PLAYWRIGHT_EXECUTABLE'),
    ],
    'browserless' => [
        'url' => env('BROWSERLESS_URL'),
        'token' => env('BROWSERLESS_TOKEN'),
    ],
],
```

### Queue Priority
```
high: Enterprise tier jobs
default: Team/Pro tier jobs
low: Free tier jobs
```

### Rate Limiting
- OpenAI API calls: Token bucket per organization
- Priority processing for paid tiers

---

## Security & Compliance

### Security Measures
- OWASP best practices
- Encrypted data at rest and in transit
- Secure authentication (bcrypt, rate limiting)
- CSRF protection
- XSS prevention
- SQL injection prevention (Eloquent ORM)
- API rate limiting

### SOC 2 Awareness
- Comprehensive audit logs
- Access control documentation
- Change management tracking
- Incident response procedures (documented)

### GDPR Compliance
- Data export functionality (user can download their data)
- Data deletion (right to be forgotten)
- Consent management for marketing communications
- Privacy policy and terms of service
- Data residency: US region (with DPA/SCCs for EU customers)

---

## Database Schema (Key Tables)

```
organizations
├── id, name, slug
├── subscription_tier, stripe_id
├── credit_balance, overdraft_balance
├── settings (JSON)
└── timestamps

departments
├── id, organization_id, name
├── credit_budget, credit_used
└── timestamps

users
├── id, organization_id, department_id
├── name, email, password
├── email_verified_at
├── fingerprint_hash, registration_ip
└── timestamps

projects
├── id, organization_id, created_by_user_id
├── name, description, language
├── check_config (JSON: spelling, grammar, seo, readability)
└── timestamps

urls
├── id, project_id
├── url, status
├── last_scanned_at
└── timestamps

scans
├── id, url_id, triggered_by_user_id
├── scan_type (quick, deep)
├── status (pending, processing, completed, failed)
├── credits_charged
├── started_at, completed_at
└── timestamps

scan_results
├── id, scan_id
├── content_snapshot (TEXT)
├── issues (JSON)
├── scores (JSON: readability, seo, etc.)
├── screenshots (JSON: paths)
└── timestamps

issues
├── id, scan_result_id
├── category (spelling, grammar, seo, readability)
├── severity (error, warning, suggestion)
├── text_excerpt, suggestion
├── dom_selector, screenshot_path
├── position (JSON: start, end)
└── timestamps

webhook_endpoints
├── id, organization_id
├── url, secret, events (JSON)
├── is_active
└── timestamps

webhook_deliveries
├── id, webhook_endpoint_id
├── event, payload (JSON)
├── response_status, response_body
├── attempts, next_retry_at
├── delivered_at
└── timestamps

credit_transactions
├── id, organization_id, user_id
├── type (subscription, purchase, usage, refund)
├── amount, balance_after
├── description, metadata (JSON)
└── timestamps

audit_logs
├── id, organization_id, user_id
├── action, auditable_type, auditable_id
├── old_values, new_values (JSON)
├── ip_address, user_agent
└── timestamps
```

---

## Implementation Phases

### Phase 1: Foundation ✅ COMPLETE
- [x] Laravel 12 project setup with PostgreSQL config
- [x] Tailwind Plus Simple Sidebar layout integration
- [x] Dark mode implementation (Filament-style with 3-way toggle)
- [x] Authentication: Fortify + OAuth (Google, M365)
- [x] Organization/Team/User models and relationships
- [x] Spatie Permission setup with RBAC (6 roles, 14 permissions)
- [x] Basic Filament admin panel (User & Organization resources)

### Phase 2: Core Scanning ✅ COMPLETE
- [x] Project and URL models
- [x] Playwright integration (configurable driver: local + Browserless)
- [x] Content extraction service (BrowserServiceManager)
- [x] OpenAI integration via Prism (Laravel AI SDK)
- [x] Tiered analysis (GPT-4o-mini for quick, GPT-4o for deep)
- [x] Large page chunking logic (PageContent class)
- [x] Horizon queue setup with priority tiers (high/default/low)
- [x] Reverb WebSocket for real-time updates

### Phase 3: Reports & Diff ✅ COMPLETE
- [x] Scan results storage
- [x] Issue categorization and display
- [x] Screenshot capture for issue locations
- [x] Diff engine (full page + issue-focused)
- [x] PDF export via Browsershot
- [x] History retention (plan-based)

### Phase 4: Billing & Credits ✅ COMPLETE
- [x] Stripe Cashier integration
- [x] Subscription tiers (Free, Pro, Team, Enterprise)
- [x] Credit system with transactions
- [ ] Department budgets (deferred to Phase 7)
- [x] Overdraft handling
- [x] Usage tracking and analytics

### Phase 5: API & Webhooks ✅ COMPLETE
- [x] Sanctum API token authentication
- [x] REST API endpoints (full parity)
- [x] Webhook system with retry logic
- [x] Webhook delivery tracking UI

### Phase 6: Security & Compliance ✅ COMPLETE
- [x] Abuse prevention (IP + fingerprint tracking)
- [x] Audit logging system
- [x] GDPR data export/deletion
- [ ] SSO integration (SAML + OIDC) for Enterprise (deferred - enterprise feature)

### Phase 7: Polish & Launch
- [x] Dashboard analytics
- [x] Notification system (in-app + email)
- [x] Sentry + Pulse integration
- [x] Performance optimization
- [x] Documentation
- [x] Testing (comprehensive Pest suite)

---

## Open Questions / Future Considerations

1. **Marketing site**: Separate project, to be built independently
2. **Castle integration**: Architecture ready, implement when needed
3. **Multi-language expansion**: Start with user-specified language, can add auto-detect later
4. **Scheduled scans**: Marked for v1.1, architecture should accommodate

---

## Appendix: Environment Variables

```env
# Database
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=onpageiq
DB_USERNAME=
DB_PASSWORD=

# AI
OPENAI_API_KEY=

# Browser
BROWSER_DRIVER=local
BROWSERLESS_URL=
BROWSERLESS_TOKEN=

# Stripe
STRIPE_KEY=
STRIPE_SECRET=
STRIPE_WEBHOOK_SECRET=

# OAuth
GOOGLE_CLIENT_ID=
GOOGLE_CLIENT_SECRET=
MICROSOFT_CLIENT_ID=
MICROSOFT_CLIENT_SECRET=

# Real-time
REVERB_APP_ID=
REVERB_APP_KEY=
REVERB_APP_SECRET=

# Monitoring
SENTRY_LARAVEL_DSN=
```

---

*Specification created: 2026-02-13*
*Status: MVP Complete (Phase 7)*

---

## Implementation Log

### Phase 1 Completion (2026-02-13)

**Files Created/Modified:**

1. **Layout & Theme:**
   - `resources/css/app.css` - Blue theme with custom dark mode variant
   - `resources/views/layouts/app.blade.php` - Simple Sidebar layout
   - `resources/views/layouts/guest.blade.php` - Auth pages layout
   - `resources/views/components/ui/dark-mode-toggle.blade.php` - 3-way toggle

2. **Models & Database:**
   - `app/Models/User.php` - Enhanced with HasRoles, OAuth, FilamentUser
   - `app/Models/Organization.php` - Credit system, subscription tiers
   - `app/Models/Department.php` - Budget management
   - `database/migrations/*` - Organization, Department tables

3. **Auth & Permissions:**
   - `app/Enums/Role.php` - 6 roles (SuperAdmin, Owner, Admin, Manager, Member, Viewer)
   - `app/Enums/Permission.php` - 14 permissions
   - `database/seeders/RolesAndPermissionsSeeder.php` - Role/permission setup
   - `app/Actions/Fortify/CreateNewUser.php` - User registration with org creation
   - `app/Http/Controllers/Auth/SocialiteController.php` - Google/M365 OAuth
   - `app/Providers/AppServiceProvider.php` - Super Admin Gate bypass
   - `bootstrap/app.php` - Permission middleware aliases

4. **Filament Admin:**
   - `app/Providers/Filament/AdminPanelProvider.php` - Admin panel config
   - `app/Filament/Resources/Users/*` - User resource with form/table
   - `app/Filament/Resources/Organizations/*` - Organization resource

**Test Users:**
- Super Admin: `admin@onpageiq.com` (access to /admin)
- Test Owner: `owner@example.com`
- Test Member: `member@example.com`

### Phase 2 Completion (2026-02-13)

**Models Created:**
- `app/Models/Project.php` - Project with check config, organization relationship
- `app/Models/Url.php` - URL with status tracking
- `app/Models/Scan.php` - Scan with credit charging, status tracking
- `app/Models/ScanResult.php` - Results with scores and metadata
- `app/Models/Issue.php` - Individual issues by category/severity

**Services Created:**
- `app/Services/Browser/BrowserServiceInterface.php` - Contract
- `app/Services/Browser/BrowserServiceManager.php` - Driver manager
- `app/Services/Browser/LocalBrowserService.php` - Local Playwright
- `app/Services/Browser/BrowserlessService.php` - Remote Browserless
- `app/Services/Browser/PageContent.php` - Content wrapper with chunking
- `app/Services/Analysis/ContentAnalyzer.php` - OpenAI analysis via Prism

**Jobs & Events:**
- `app/Jobs/ScanUrlJob.php` - Main scan orchestration job
- `app/Events/ScanProgress.php` - Progress broadcast
- `app/Events/ScanCompleted.php` - Completion broadcast
- `app/Events/ScanFailed.php` - Failure broadcast

**Configuration:**
- `config/onpageiq.php` - Browser, AI, scanning, credits config
- `config/horizon.php` - Priority queues (high/default/low)
- `routes/channels.php` - Broadcast channel authorization

### Phase 3 Completion (2026-02-13)

**Livewire Components:**
- `app/Livewire/Projects/ProjectList.php` - Project listing with search/pagination
- `app/Livewire/Projects/ProjectShow.php` - Project detail with URL management
- `app/Livewire/Scans/ScanResults.php` - Scan results with filtering
- `app/Livewire/Scans/IssueList.php` - Issue list with category/severity filters
- `app/Livewire/Scans/ScanComparison.php` - Scan diff comparison

**Blade Views:**
- `resources/views/livewire/projects/project-list.blade.php`
- `resources/views/livewire/projects/project-show.blade.php`
- `resources/views/livewire/scans/scan-results.blade.php`
- `resources/views/livewire/scans/issue-list.blade.php`
- `resources/views/livewire/scans/scan-comparison.blade.php`

**Screenshot Services:**
- `app/Services/Screenshot/IssueScreenshotService.php` - Batch issue screenshots
- Extended `BrowserServiceInterface` with `screenshotWithHighlight()` method
- `app/Console/Commands/CleanupOrphanedScreenshots.php`

**Diff Engine:**
- `app/Services/Diff/ScanDiffService.php` - Compare scan results
- `app/Services/Diff/ScanComparison.php` - DTO for comparison data

**PDF Export:**
- `app/Services/Export/PdfExportService.php` - Browsershot integration
- `app/Http/Controllers/ExportController.php` - Download endpoints
- `resources/views/exports/scan-result.blade.php` - PDF template
- `resources/views/exports/scan-comparison.blade.php` - Comparison PDF template

**History Retention:**
- `app/Services/Retention/HistoryRetentionService.php` - Tier-based cleanup
- `app/Console/Commands/CleanupScanHistory.php` - Cleanup command
- `routes/console.php` - Scheduled cleanup tasks

**Routes Added:**
- `GET /projects` - ProjectList
- `GET /projects/{project}` - ProjectShow
- `GET /scans/{scan}` - ScanResults
- `GET /scans/{scan}/compare` - ScanComparison
- `GET /scans/{scan}/export/pdf` - PDF export
- `GET /scans/{scan}/compare/{baseline}/export/pdf` - Comparison PDF export

### Phase 4 Completion (2026-02-13)

**Stripe Cashier Integration:**
- `config/cashier.php` - Configured Organization as billable model
- `database/migrations/*_create_customer_columns.php` - Cashier columns on organizations
- `database/migrations/*_create_subscriptions_table.php` - Modified for organization_id

**Subscription Tiers:**
- `config/onpageiq.php` - Added tiers config (Free, Pro, Team, Enterprise)
- `config/onpageiq.php` - Added credit packs config (50, 150, 500 credits)
- `app/Services/Billing/SubscriptionService.php` - Tier management, feature checks

**Credit System:**
- `app/Models/CreditTransaction.php` - Transaction model with types
- `database/migrations/*_create_credit_transactions_table.php` - Transaction tracking
- `database/factories/CreditTransactionFactory.php` - Factory with states
- `app/Services/Billing/CreditService.php` - Credit management, history, stats

**Billing Livewire Components:**
- `app/Livewire/Billing/SubscriptionManager.php` - Plan viewing/changing
- `app/Livewire/Billing/CreditBalance.php` - Balance display widget
- `app/Livewire/Billing/CreditPurchase.php` - Credit pack purchasing
- `app/Livewire/Billing/BillingHistory.php` - Transaction history

**Blade Views:**
- `resources/views/livewire/billing/subscription-manager.blade.php`
- `resources/views/livewire/billing/credit-balance.blade.php`
- `resources/views/livewire/billing/credit-purchase.blade.php`
- `resources/views/livewire/billing/billing-history.blade.php`

**Stripe Webhooks:**
- `app/Http/Controllers/Webhooks/StripeWebhookController.php` - Extends Cashier
- `bootstrap/app.php` - CSRF exception for webhook route
- Handles: subscription.created, subscription.updated, subscription.deleted
- Handles: invoice.payment_succeeded, checkout.session.completed (credit purchases)

**Routes Added:**
- `GET /billing` - SubscriptionManager
- `GET /billing/credits` - CreditPurchase
- `GET /billing/history` - BillingHistory
- `GET /billing/success` - Subscription success redirect
- `GET /billing/credits/success` - Credit purchase success redirect
- `POST /stripe/webhook` - Stripe webhook endpoint

### Phase 5 Completion (2026-02-13)

**Sanctum API Authentication:**
- Installed Laravel Sanctum v4
- Added `HasApiTokens` trait to User model
- `app/Livewire/Settings/ApiTokens.php` - Token management UI
- `resources/views/livewire/settings/api-tokens.blade.php`

**REST API Endpoints:**
- `routes/api.php` - API v1 routes with auth:sanctum middleware
- `app/Http/Controllers/Api/V1/AuthController.php` - Token create/revoke
- `app/Http/Controllers/Api/V1/ProjectController.php` - CRUD operations
- `app/Http/Controllers/Api/V1/UrlController.php` - URL management
- `app/Http/Controllers/Api/V1/ScanController.php` - Scan triggering
- `app/Http/Controllers/Api/V1/CreditController.php` - Balance/transactions
- `app/Http/Controllers/Api/V1/WebhookController.php` - Webhook CRUD

**Webhook System:**
- `app/Models/WebhookEndpoint.php` - Endpoint model with events
- `app/Models/WebhookDelivery.php` - Delivery tracking with retry
- `database/migrations/*_create_webhook_endpoints_table.php`
- `database/migrations/*_create_webhook_deliveries_table.php`
- `app/Services/Webhook/WebhookDispatcher.php` - Event dispatching
- `app/Jobs/SendWebhookJob.php` - Async delivery with exponential backoff

**Webhook UI:**
- `app/Livewire/Webhooks/WebhookEndpoints.php` - Endpoint management
- `app/Livewire/Webhooks/WebhookDeliveries.php` - Delivery history/retry
- `resources/views/livewire/webhooks/webhook-endpoints.blade.php`
- `resources/views/livewire/webhooks/webhook-deliveries.blade.php`

**Routes Added:**
- `GET /api/tokens` - API token management
- `GET /api/webhooks` - Webhook endpoints
- `GET /api/webhooks/{endpoint}/deliveries` - Deliveries for endpoint
- `GET /api/webhooks/deliveries` - All deliveries
- API v1: `/api/v1/auth/token`, `/api/v1/projects`, `/api/v1/urls`, `/api/v1/scans`, `/api/v1/credits`, `/api/v1/webhooks`

### Phase 6 Completion (2026-02-13)

**Abuse Prevention:**
- `app/Services/Security/AbusePreventionService.php` - IP tracking, fingerprint hashing, disposable email blocking
- Disposable email domain blocklist
- IP and fingerprint rate limiting for free accounts
- Risk scoring for suspicious registrations

**Audit Logging:**
- `app/Models/AuditLog.php` - Audit log model
- `database/migrations/*_create_audit_logs_table.php`
- `app/Services/Security/AuditService.php` - Logging service
- Logs: created, updated, deleted, viewed, exported, login, logout, API access
- Automatic sensitive field redaction

**GDPR Compliance:**
- `app/Services/Security/GdprService.php` - GDPR service
- `exportUserData()` - Export all user data to ZIP
- `deleteUserData()` - Right to be forgotten
- `deleteOrganizationData()` - Full organization deletion
- Scheduled deletion with grace period

### Phase 7 Progress (2026-02-13)

**Dashboard Analytics (Task 32) ✅:**
- `app/Livewire/Dashboard/Dashboard.php` - Dashboard component with stats
- `resources/views/livewire/dashboard/dashboard.blade.php` - Dashboard UI
- Features: Credit balance, scan stats (30 days), project count, subscription tier
- Recent projects and scans lists with status badges
- Credit usage chart (credits added, used, net change)
- Quick action cards (new project, buy credits, API access)
- Updated `routes/web.php` to use Dashboard component

**Notification System (Task 33) ✅:**
- `app/Notifications/ScanCompletedNotification.php` - Scan completion (mail + database)
- `app/Notifications/CreditsLowNotification.php` - Low balance warning
- `app/Notifications/CreditsDepletedNotification.php` - Credits depleted alert
- `app/Notifications/TeamInviteNotification.php` - Team invitations
- `app/Livewire/Notifications/NotificationDropdown.php` - Header dropdown component
- `app/Livewire/Notifications/NotificationList.php` - Full notification page
- `resources/views/livewire/notifications/notification-dropdown.blade.php`
- `resources/views/livewire/notifications/notification-list.blade.php`
- `app/Services/Notification/NotificationService.php` - Notification dispatching service
- `database/migrations/*_create_notifications_table.php` - Laravel notifications table
- `database/migrations/*_add_notification_preferences_to_users_table.php` - User preferences
- User model updated with notification_preferences cast
- Route: `GET /notifications` - NotificationList

**Comprehensive Pest Tests (Task 34) ✅:**
- `tests/Pest.php` - Updated to enable RefreshDatabase for Unit and Feature tests
- `tests/Unit/Models/OrganizationTest.php` - 11 tests for Organization model
  - Credit operations, tier checks, retention, default checks
- `tests/Unit/Services/CreditServiceTest.php` - 8 tests for CreditService
  - Add, deduct, refund credits, usage stats, balance checks
- `tests/Feature/Livewire/DashboardTest.php` - 9 tests for Dashboard component
  - Renders, displays stats, recent projects/scans, empty states
- `tests/Feature/Livewire/BillingTest.php` - 9 tests for billing components
  - Subscription manager, credit purchase, billing history
- `tests/Feature/Api/ProjectApiTest.php` - 10 tests for Project API
  - CRUD operations, authorization, validation, pagination
- `tests/Feature/Notifications/NotificationTest.php` - 10 tests for notifications
  - Send notifications, dropdown/list components, mark read, delete
- `tests/Feature/Auth/AuthenticationTest.php` - 12 tests for authentication
  - Route protection, OAuth routes, authenticated access
- **Total: 71 tests, 130 assertions - ALL PASSING**

**Sentry + Pulse Integration (Task 35) ✅:**
- Installed `sentry/sentry-laravel` v4.20 for error tracking
- Installed `laravel/pulse` v1.5 for application monitoring
- `config/sentry.php` - Sentry configuration
- `config/pulse.php` - Pulse configuration
- `database/migrations/*_create_pulse_tables.php` - Pulse data storage
- `app/Providers/AppServiceProvider.php` - Added viewPulse Gate for authorization
- `.env.example` - Added SENTRY_LARAVEL_DSN, SENTRY_TRACES_SAMPLE_RATE, PULSE_ENABLED, PULSE_PATH
- Pulse dashboard accessible at `/pulse` for Super Admins only

**Performance Optimization (Task 36) ✅:**
- `app/Services/Billing/SubscriptionService.php` - Added caching for tier configurations (1 hour TTL)
- `database/migrations/*_add_performance_indexes.php` - Added database indexes:
  - `scans`: status+created_at, triggered_by_user_id
  - `urls`: project_id+status, last_scanned_at
  - `credit_transactions`: organization_id+created_at, organization_id+type
  - `audit_logs`: organization_id+created_at, auditable_type+auditable_id
  - `webhook_deliveries`: webhook_endpoint_id+created_at, delivered_at
  - `notifications`: notifiable_type+notifiable_id+read_at
- Migration uses idempotent index creation (checks if index exists before creating)

**Documentation (Task 37) ✅:**
- `docs/API.md` - Complete API reference documentation
  - Authentication (Sanctum tokens)
  - Projects, URLs, Scans endpoints
  - Credits and Webhooks
  - Rate limits and error responses
- `README.md` - Updated with comprehensive setup guide
  - Installation instructions
  - Environment configuration
  - Queue workers and monitoring
  - Subscription tiers overview

---

## Phase 7 Complete - MVP Ready

All MVP features have been implemented:
- Dashboard with analytics
- In-app and email notifications
- Sentry + Pulse monitoring
- Performance optimizations (caching, indexes)
- Comprehensive test suite (71 tests, 130 assertions)
- API and developer documentation

**Total Tests: 71 passing**
