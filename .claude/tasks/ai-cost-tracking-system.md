# AI Cost Tracking & Analytics System

**Status:** ✅ COMPLETED
**Created:** January 29, 2026
**Completed:** January 29, 2026

---

## Overview

Enhance the existing `AIUsageLog` system to store full prompt/response content (auto-redacted), add hierarchical purpose tracking, implement budget controls with confirmation modals, and build a comprehensive Filament admin dashboard for super admins.

**Key Decisions:**
- Full prompt + response storage with auto-redaction via `SensitiveDataDetector`
- Full attribution hierarchy: Organization → User → Project → Document
- Daily + Monthly aggregation tables for performance
- Soft budget limits with confirmation modal (per-org + per-user)
- Super admin only Filament dashboard with full analytics suite
- Indefinite retention

---

## Implementation Tasks

| # | Task | Status | Description |
|---|------|--------|-------------|
| 1 | Create migrations | ✅ Done | Created ai_usage_logs, ai_usage_daily, ai_usage_monthly, ai_budgets tables |
| 2 | Create AIUsageCategory enum | ✅ Done | Hierarchical purpose tracking (category + detail) |
| 3 | Create AIUsageRedactionService | ✅ Done | Auto-redacts emails, phones, SSN, credit cards, API keys, etc. |
| 4 | Create AIUsageLog model | ✅ Done | Full model with logUsage(), cost calculation, scopes |
| 5 | Create aggregation models | ✅ Done | AIUsageDaily, AIUsageMonthly with aggregation methods |
| 6 | Create AIBudget model | ✅ Done | Budget limits and tracking with computed attributes |
| 7 | Create AIBudgetService | ✅ Done | Budget checking, recording, reset functionality |
| 8 | Update AITextBuilder | Skipped | Not needed - AIUsageLog::logUsage() handles everything |
| 9 | Create aggregation commands | ✅ Done | ai:aggregate-daily, ai:aggregate-monthly, ai:reset-budgets |
| 10 | Create Filament resources | ✅ Done | AIUsageLogResource, AIBudgetResource with pages/tables/forms |
| 11 | Create Filament dashboard | ✅ Done | AIDashboard with 6 widgets (stats, charts, tables) |
| 12 | Create BudgetConfirmationModal | ✅ Done | Livewire component with blade template |
| 13 | Write tests | ✅ Done | 30 tests passing (77 assertions) |

---

## Files to Create

```
# Migrations
database/migrations/XXXX_enhance_ai_usage_logs_table.php
database/migrations/XXXX_create_ai_usage_daily_table.php
database/migrations/XXXX_create_ai_usage_monthly_table.php
database/migrations/XXXX_create_ai_budgets_table.php

# Enums & DTOs
app/Enums/AIUsageCategory.php
app/Services/AI/DTOs/RedactionResult.php
app/Services/AI/DTOs/BudgetCheckResult.php

# Services
app/Services/AI/AIUsageRedactionService.php
app/Services/AI/AIBudgetService.php

# Models
app/Models/AIUsageDaily.php
app/Models/AIUsageMonthly.php
app/Models/AIBudget.php

# Commands
app/Console/Commands/AggregateAIUsageDaily.php
app/Console/Commands/AggregateAIUsageMonthly.php
app/Console/Commands/ResetMonthlyAIBudgets.php

# Filament
app/Filament/Resources/AIUsageLogResource.php
app/Filament/Resources/AIUsageLogResource/Pages/ListAIUsageLogs.php
app/Filament/Resources/AIUsageLogResource/Pages/ViewAIUsageLog.php
app/Filament/Resources/AIBudgetResource.php
app/Filament/Resources/AIBudgetResource/Pages/*.php
app/Filament/Pages/AIDashboard.php
app/Filament/Widgets/AIUsageStatsOverview.php
app/Filament/Widgets/AICostOverTimeChart.php
app/Filament/Widgets/CostByProviderChart.php
app/Filament/Widgets/CostByCategoryChart.php
app/Filament/Widgets/TopOrganizationsTable.php
app/Filament/Widgets/TopUsersTable.php
resources/views/filament/pages/ai-dashboard.blade.php

# Livewire
app/Livewire/AI/BudgetConfirmationModal.php
resources/views/livewire/ai/budget-confirmation-modal.blade.php

# Tests
tests/Feature/AI/AIUsageLoggingTest.php
tests/Feature/AI/AIBudgetServiceTest.php
tests/Feature/AI/AIUsageAggregationTest.php
```

## Files to Modify

```
app/Models/AIUsageLog.php              # Add new fields, enhance logUsage()
app/Services/AI/AITextBuilder.php       # Content capture, redaction, budget checks
app/Providers/Filament/AdminPanelProvider.php  # Super admin authorization
routes/console.php                      # Schedule aggregation commands
```

---

## Database Schema

### ai_usage_logs (enhanced)

```php
// Add to existing table:
$table->longText('prompt_content')->nullable();
$table->longText('response_content')->nullable();
$table->boolean('content_redacted')->default(false);
$table->json('redaction_summary')->nullable();
$table->string('category')->nullable();         // AIUsageCategory enum
$table->string('purpose_detail')->nullable();   // Free-form detail
$table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
$table->foreignId('document_id')->nullable()->constrained()->nullOnDelete();
$table->boolean('budget_override')->default(false);
$table->foreignId('budget_override_by')->nullable()->constrained('users');
```

### ai_usage_daily

```php
$table->date('date');
$table->foreignId('organization_id')->nullable();
$table->foreignId('user_id')->nullable();
$table->string('category')->nullable();
$table->string('provider')->nullable();
$table->string('model')->nullable();
$table->unsignedInteger('request_count');
$table->unsignedInteger('success_count');
$table->unsignedInteger('failure_count');
$table->unsignedBigInteger('total_prompt_tokens');
$table->unsignedBigInteger('total_completion_tokens');
$table->unsignedBigInteger('total_tokens');
$table->decimal('total_cost', 12, 6);
$table->unsignedBigInteger('total_duration_ms');
// Unique: date + org + user + category + provider + model
```

### ai_usage_monthly

```php
$table->unsignedSmallInteger('year');
$table->unsignedTinyInteger('month');
$table->foreignId('organization_id')->nullable();
$table->foreignId('user_id')->nullable();
$table->string('category')->nullable();
$table->unsignedInteger('request_count');
$table->unsignedInteger('success_count');
$table->unsignedBigInteger('total_tokens');
$table->decimal('total_cost', 14, 6);
// Unique: year + month + org + user + category
```

### ai_budgets

```php
$table->foreignId('organization_id')->nullable();
$table->foreignId('user_id')->nullable();
$table->decimal('monthly_limit', 10, 2)->nullable();
$table->decimal('warning_threshold', 5, 2)->default(80);
$table->decimal('current_month_usage', 12, 6)->default(0);
$table->date('current_period_start')->nullable();
$table->boolean('is_active')->default(true);
$table->boolean('allow_override')->default(true);
// Unique: organization_id + user_id
```

---

## Key Implementation Details

### AIUsageCategory Enum

```php
enum AIUsageCategory: string
{
    case DOCUMENT_ANALYSIS = 'document_analysis';
    case ESTIMATE_ANALYSIS = 'estimate_analysis';
    case BRIEF_ANALYSIS = 'brief_analysis';
    case PROJECT_EXTRACTION = 'project_extraction';
    case SUMMARIZATION = 'summarization';
    case CLASSIFICATION = 'classification';
    case QUESTION_GENERATION = 'question_generation';
    case FLAG_EXTRACTION = 'flag_extraction';
    case GENERAL = 'general';
}
```

### AITextBuilder Enhancement

Add fluent methods:
```php
public function withCategory(AIUsageCategory $category): self
public function withPurposeDetail(string $detail): self
public function forProject(?int $projectId): self
public function forDocument(?int $documentId): self
public function forLoggable(?Model $loggable): self
public function withBudgetOverride(bool $override = true): self
```

Update `generate()` to:
1. Capture prompt content before AI call
2. Capture response content after AI call
3. Redact both via `AIUsageRedactionService`
4. Pass all metadata to `AIUsageLog::logUsage()`
5. Update budget usage tracking

### AIUsageLog::logUsage() Enhanced Signature

```php
public static function logUsage(
    string $provider,
    string $model,
    int $promptTokens,
    int $completionTokens,
    int $durationMs = 0,
    ?string $taskType = null,
    ?Model $loggable = null,
    bool $success = true,
    ?string $errorMessage = null,
    ?array $metadata = null,
    // NEW parameters:
    ?string $promptContent = null,
    ?string $responseContent = null,
    ?AIUsageCategory $category = null,
    ?string $purposeDetail = null,
    ?int $projectId = null,
    ?int $documentId = null,
    bool $budgetOverride = false,
    ?int $budgetOverrideBy = null
): self
```

### Budget Flow

1. Before AI call: `AIBudgetService::checkBudget()`
2. If over budget + allow_override: dispatch `show-budget-confirmation` event
3. User sees modal with current usage, limit, and confirm/cancel buttons
4. On confirm: set `budgetOverride = true`, proceed with AI call
5. On cancel: abort the operation
6. After AI call: `AIBudgetService::recordUsage()` updates denormalized counters

### Scheduled Commands

```php
// routes/console.php
Schedule::command('ai:aggregate-daily')->dailyAt('01:00');
Schedule::command('ai:aggregate-monthly')->monthlyOn(1, '02:00');
Schedule::command('ai:reset-budgets')->monthlyOn(1, '00:05');
```

---

## Filament Dashboard Widgets

| Widget | Type | Data Source |
|--------|------|-------------|
| AIUsageStatsOverview | Stats cards | Today/Week/Month/AllTime costs |
| AICostOverTimeChart | Line chart | Last 30 days from ai_usage_daily |
| CostByProviderChart | Pie chart | This month by provider |
| CostByCategoryChart | Pie chart | This month by category |
| TopOrganizationsTable | Table | Top 10 orgs by spend |
| TopUsersTable | Table | Top 10 users by spend |

---

## Verification

```bash
# Run migrations
php artisan migrate

# Run tests
php artisan test tests/Feature/AI/

# Test aggregation commands
php artisan ai:aggregate-daily --date=2026-01-28
php artisan ai:aggregate-monthly

# Access Filament dashboard
# Navigate to /admin (requires super_admin role)

# Manual testing
# 1. Trigger an AI call (e.g., analyze a document)
# 2. Check /admin - verify log appears with prompt/response
# 3. Set a low budget for your org ($0.01)
# 4. Trigger another AI call - verify confirmation modal appears
# 5. Confirm override - verify budget_override=true in log
# 6. Check dashboard charts update
```

---

## Notes

- Existing `SensitiveDataDetector` handles: emails, phones, SSNs, credit cards, API keys, AWS keys, private keys, IP addresses
- Content redaction replaces sensitive values with `[REDACTED:type]` markers
- Budget checks happen client-side (Livewire) to avoid blocking server-side
- Aggregation tables use upsert for idempotent re-runs
- FilamentPHP v5.1.1 is already installed with empty AdminPanelProvider

---

## Implementation Notes (Post-Completion)

### Files Created

**Migrations:**
- `database/migrations/2026_02_13_210001_create_ai_usage_logs_table.php`
- `database/migrations/2026_02_13_210002_create_ai_usage_daily_table.php`
- `database/migrations/2026_02_13_210003_create_ai_usage_monthly_table.php`
- `database/migrations/2026_02_13_210004_create_ai_budgets_table.php`

**Enums & DTOs:**
- `app/Enums/AIUsageCategory.php`
- `app/Services/AI/DTOs/RedactionResult.php`
- `app/Services/AI/DTOs/BudgetCheckResult.php`

**Services:**
- `app/Services/AI/AIUsageRedactionService.php`
- `app/Services/AI/AIBudgetService.php`

**Models:**
- `app/Models/AIUsageDaily.php`
- `app/Models/AIUsageMonthly.php`
- `app/Models/AIBudget.php`

**Commands:**
- `app/Console/Commands/AggregateAIUsageDaily.php`
- `app/Console/Commands/AggregateAIUsageMonthly.php`
- `app/Console/Commands/ResetMonthlyAIBudgets.php`

**Filament Resources:**
- `app/Filament/Resources/AIUsageLogs/AIUsageLogResource.php`
- `app/Filament/Resources/AIUsageLogs/Tables/AIUsageLogsTable.php`
- `app/Filament/Resources/AIUsageLogs/Pages/ListAIUsageLogs.php`
- `app/Filament/Resources/AIUsageLogs/Pages/ViewAIUsageLog.php`
- `app/Filament/Resources/AIBudgets/AIBudgetResource.php`
- `app/Filament/Resources/AIBudgets/Schemas/AIBudgetForm.php`
- `app/Filament/Resources/AIBudgets/Tables/AIBudgetsTable.php`
- `app/Filament/Resources/AIBudgets/Pages/CreateAIBudget.php`
- `app/Filament/Resources/AIBudgets/Pages/EditAIBudget.php`
- `app/Filament/Resources/AIBudgets/Pages/ListAIBudgets.php`

**Filament Dashboard & Widgets:**
- `app/Filament/Pages/AIDashboard.php`
- `app/Filament/Widgets/AIUsageStatsOverview.php`
- `app/Filament/Widgets/AICostOverTimeChart.php`
- `app/Filament/Widgets/CostByProviderChart.php`
- `app/Filament/Widgets/CostByCategoryChart.php`
- `app/Filament/Widgets/TopOrganizationsTable.php`
- `app/Filament/Widgets/TopUsersTable.php`
- `resources/views/filament/pages/ai-dashboard.blade.php`

**Livewire:**
- `app/Livewire/AI/BudgetConfirmationModal.php`
- `resources/views/livewire/ai/budget-confirmation-modal.blade.php`

**Factories:**
- `database/factories/AIUsageLogFactory.php`
- `database/factories/AIUsageDailyFactory.php`
- `database/factories/AIUsageMonthlyFactory.php`
- `database/factories/AIBudgetFactory.php`

**Tests:**
- `tests/Feature/AI/AIUsageLoggingTest.php` (10 tests)
- `tests/Feature/AI/AIBudgetServiceTest.php` (10 tests)
- `tests/Feature/AI/AIUsageAggregationTest.php` (11 tests)

### Files Modified

- `app/Models/AIUsageLog.php` - Added HasFactory, new fields, enhanced logUsage() with budget tracking
- `app/Models/User.php` - Added FilamentUser interface with canAccessPanel() for super_admin check

### Key Implementation Details

1. **Filament v5 API Changes**: Required using union types for navigation properties (`string|UnitEnum|null`), `Schema` parameter instead of `Form/Infolist`, and non-static `$heading`/`$view` properties.

2. **SQLite Date Handling**: SQLite stores date columns with `00:00:00` time component. Fixed by:
   - Using `whereDate()` instead of `where('date', ...)` in tests
   - Converting date strings to Carbon objects in `aggregateForDate()` method

3. **AIBudget Table Name**: Laravel auto-pluralization caused issues (`a_i_budgets` vs `ai_budgets`). Fixed by adding explicit `$table = 'ai_budgets'` property.

4. **Organization Roles**: Tests use 'owner' role (valid enum values: owner, executive, team_lead, ic)

### Test Results

```
Tests:    30 passed (77 assertions)
Duration: 1.73s

- AIUsageLoggingTest: 10 tests
- AIBudgetServiceTest: 10 tests
- AIUsageAggregationTest: 10 tests
```

### Implementation Date

February 13, 2026
