# Project Detail Enhancement Specification

## Overview

Comprehensive enhancement of the project dashboard with overview cards, findings management, issue workflow, accessibility checks, and social preview features.

---

## 1. Overview Cards (Clickable Filters)

Three stat cards at the top of the project dashboard. **Clicking a card filters the findings table below** and highlights the active card.

| Card | Content | Filter Action |
|------|---------|---------------|
| **Typos Found** | `{count}` + "Review website typos and grammar issues. Ignore any false positives." | Shows all content issues |
| **Pages Have Issues** | `{count}` + "Automate this report to catch new typos right away." | Shows pages with Warning/Error status |
| **Pages Look Good** | `{count}` + "Get peace of mind on pages that are typo free." | Shows pages with Success status |

**Behavior:**
- Cards highlight when active filter
- Clicking active card clears filter (shows all)
- Counts update after scan completion
- During active scan: show progress indicator ("Scanning 12/50 pages...")

---

## 2. Summary Section

### 2.1 Scan Metadata Display

| Field | Description |
|-------|-------------|
| **Automatic Scans** | Link to Project Settings for schedule configuration |
| **Last Scan Date** | Timestamp of most recent completed scan |
| **Language** | Primary detected language + secondary if mixed (e.g., "English (Spanish detected)") |

### 2.2 Page Status Grids (Tiered System)

Three-tier status system: **Success** / **Warning** / **Error**

| Status | Definition | Icon |
|--------|------------|------|
| Success | Page loaded, no issues found | Green checkmark |
| Warning | Page loaded but has parsing issues, partial content, or minor problems | Yellow warning |
| Error | HTTP 4xx/5xx, timeout, completely failed to load | Red X |

**Grid Behavior:**
- Responsive multi-column layout in scrollable container
- Show shortened URL path (e.g., `/contact`)
- **Click URL** → Internal page detail view
- **Separate "Visit" icon** → Opens external page in new tab
- Warning/Error indicators link to page detail view with explanation

### 2.3 Filter Dropdown

- **Multi-select with search** capability
- Supports sites with hundreds of pages
- Filter findings table by selected pages

---

## 3. Findings Table

### 3.1 Columns

| Column | Content |
|--------|---------|
| **Checkbox** | For bulk selection |
| **Page** | URL path (e.g., `/contact`) |
| **Type** | Issue category chip |
| **Potential Typos** | Count for that page |
| **Suggestions** | Inline AI suggestion with **copy button** |
| **Menu** | Actions dropdown |

### 3.2 Filter Chips

Prominent filter chips above table:
- **All** | **Content** (spelling/grammar) | **Accessibility** | **Meta**

### 3.3 Issue Actions (Full Workflow)

| Action | Behavior |
|--------|----------|
| **Ignore** | Permanently hides this specific issue |
| **Add to Dictionary** | Opens dictionary scope selector |
| **Report False Positive** | Categorized report modal (see 3.4) |
| **Assign to Team Member** | User picker, moves to Kanban board |
| **Add Note** | Text note, visible to all team members |
| **Mark as Fixed** | Sets "Pending Verification" status, auto-verified on next scan |

### 3.4 False Positive Report Modal

Categories:
- Valid word
- Industry term
- Brand name
- Other

Optional context field for explanation.

### 3.5 Bulk Actions

Full bulk support via checkbox selection:
- Bulk ignore
- Bulk add to dictionary
- Bulk assign
- Bulk move state
- Bulk export

---

## 4. Dictionary System (Hierarchical)

### 4.1 Scope Levels

| Level | Description |
|-------|-------------|
| **Organization** | Applies to all projects in org |
| **Project** | Applies only to specific project |

### 4.2 Conflict Resolution

When same word exists in both org and project dictionaries with different settings:
- **Show both entries** to user
- User decides per-instance which to apply
- UI clearly indicates conflict state

### 4.3 Effect Timing

Dictionary entries apply to **future scans** automatically. No re-scan required for already-processed content.

---

## 5. Issue Workflow Board (Kanban)

### 5.1 Columns (4-Column with Review)

```
Open → In Progress → Review → Resolved
```

### 5.2 Permissions

- **Flexible**: Any team member can move issues through any state
- Assignee gets notification when assigned
- Dashboard widget shows assigned issues

### 5.3 Fix Verification

When "Mark as Fixed" is clicked:
1. Issue moves to "Pending Verification" state
2. Next scan checks if issue still exists
3. Auto-resolves if fixed, re-opens if still present

---

## 6. Scheduled Scans

### 6.1 Configuration Location

**Project Settings page** → Dedicated "Automatic Scans" section

### 6.2 Plan-Based Availability

| Plan | Scheduling |
|------|------------|
| Free | Not available |
| Paid | Available with credit consumption |

### 6.3 Credit Handling

- **Fixed per-URL cost** (1 credit = 1 URL scanned)
- **Pre-scan check**: Scan will NOT start if insufficient credits
- **Notification**: Email notification at scan time if insufficient credits
- No partial scans - either runs completely or doesn't start

### 6.4 New URL Discovery

When scan discovers new internal links not in original URL list:
- **Flag for approval** - list discovered URLs
- User approves which to add to project
- Approved URLs included in future scans

---

## 7. Page Detail View

Accessed from URL grids or warning/error links.

### 7.1 Sections

| Section | Content |
|---------|---------|
| **Screenshots** | Desktop + Mobile viewport captures |
| **Issues List** | All findings for this page |
| **Meta Data Accordion** | Title, description, OG tags (see Section 8) |
| **Core Web Vitals** | LCP, FID, CLS scores |
| **Performance Metrics** | Load time, page size, request count |
| **Accessibility Score** | WCAG highlights overview |
| **Readability Score** | Flesch-Kincaid grade level |
| **Content Stats** | Word count, thin content warnings |

### 7.2 Screenshot Capture

- **Desktop + Mobile** viewports captured during every scan
- Stored for historical reference
- Responsive design verification

### 7.3 Core Web Vitals

| Metric | Description |
|--------|-------------|
| **LCP** | Largest Contentful Paint - loading performance |
| **FID** | First Input Delay - interactivity |
| **CLS** | Cumulative Layout Shift - visual stability |

Scores color-coded: Green (good) / Yellow (needs improvement) / Red (poor)

---

## 8. Meta Data & Social Previews

### 8.1 Accordion Structure

Expandable accordion showing:
- Page title
- Meta description
- Open Graph tags (og:title, og:description, og:image, etc.)
- Twitter Card tags

### 8.2 Preview Platforms

**Show all platforms:**
- **Google Search (SERP)** - How page appears in search results
- **Facebook** - Open Graph preview
- **Twitter/X** - Twitter Card preview
- **LinkedIn** - Professional share preview

### 8.3 Validation

Each preview shows:
- Visual rendering of how it will appear
- **Validation warnings** (e.g., "Title too long for Twitter", "OG image wrong aspect ratio")

### 8.4 AI Suggestions (On-Demand)

- **"Get Suggestions" button** triggers AI generation
- **Uses credits** when clicked (not pre-computed)
- Suggests improved meta descriptions, titles, etc.
- Shows validation issues with recommended fixes

---

## 9. Accessibility Checks (Basic WCAG)

### 9.1 Checks Included

| Check | Detection |
|-------|-----------|
| **Alt Tags** | Smart detection with confidence scoring |
| **Color Contrast** | Text against background analysis |
| **Heading Hierarchy** | Proper H1-H6 structure |
| **Form Labels** | Input fields have associated labels |

### 9.2 Alt Tag Smart Detection

- Heuristics to identify likely decorative images (size, position, CSS)
- Flag images that are **not high confidence** as decorative
- **"Mark as Decorative" toggle** - applies to specific image URL
- Marked decorative images skipped in future scans

### 9.3 Issue Categorization

Accessibility issues appear in combined findings table with **filter chips**:
- All | Content | **Accessibility** | Meta

---

## 10. Organization Overview Dashboard

### 10.1 Landing Page

**Default landing page** after login (not project-specific)

### 10.2 Full Health Dashboard Metrics

| Metric Category | Details |
|-----------------|---------|
| **Issue Counts** | Total open, by severity, by project |
| **Trends** | Issues over time, resolution rate |
| **Credit Usage** | Current balance, burn rate |
| **Team Activity** | Recent actions, assignments |
| **Scheduled Scans** | Upcoming scan calendar |
| **Alerts** | Insufficient credits, failed scans, critical issues |

---

## 11. Export & Integrations

### 11.1 Export Formats

| Format | Use Case |
|--------|----------|
| **CSV** | Spreadsheet analysis |
| **PDF Report** | Stakeholder sharing, documentation |
| **JSON API** | Custom integrations, automation |

### 11.2 Notifications

**Email only** for:
- Scan completion
- Insufficient credits for scheduled scan
- Critical issues found

---

## 12. Regional Spelling & Language

### 12.1 Project Language Configuration

Project settings allow specifying:
- **Primary language** (English, French, Spanish, etc.)
- **Regional variant** (US English, UK English, Australian English, Canadian French, etc.)

### 12.2 Spelling Behavior

- "Colour" not flagged as error for UK English projects
- "Color" not flagged for US English projects
- Dictionary respects regional settings
- AI suggestions use appropriate regional vocabulary

---

## 13. Link Checking

### 13.1 Broken Link Detection

| Link Type | Check |
|-----------|-------|
| **Internal Links** | Verify target page exists and responds |
| **External Links** | HTTP HEAD request to verify accessibility |
| **Anchor Links** | Verify target ID exists on page |

### 13.2 Link Status Categories

- **Valid** - Link works correctly
- **Broken** - 404 or unreachable
- **Redirect** - Link redirects (flag for review)
- **Timeout** - Link timed out (may be temporary)

### 13.3 Issue Categorization

Broken link issues appear in findings with filter chip: **Links**

Updated filter chips: All | Content | Accessibility | Meta | **Links** | SEO

---

## 14. Content Analysis

### 14.1 Readability Scoring

| Metric | Description |
|--------|-------------|
| **Flesch-Kincaid Grade** | US grade level required to understand |
| **Flesch Reading Ease** | 0-100 score (higher = easier) |
| **Average Sentence Length** | Words per sentence |
| **Complex Word Percentage** | Words with 3+ syllables |

Project can set **target reading level** (e.g., "8th grade or below")

### 14.2 Word Count & Thin Content

- Display word count per page
- **Thin content warning** if below threshold (default: 300 words, configurable)
- Exclude navigation, footer, boilerplate from count

### 14.3 Duplicate Content Detection

- Flag **identical paragraphs** appearing on multiple pages
- Flag **near-duplicate content** (>80% similarity)
- Exclude expected duplicates (headers, footers, legal text)
- Configurable similarity threshold

### 14.4 Content Freshness

- Track **last-modified date** from HTTP headers or meta tags
- Flag pages not updated in X months (configurable, default: 12 months)
- "Stale content" indicator in page detail view

---

## 15. Technical SEO Checks

### 15.1 Schema.org / Structured Data Validation

| Check | Description |
|-------|-------------|
| **JSON-LD Validity** | Parse and validate JSON-LD blocks |
| **Schema Type** | Identify schema types used (Article, Product, etc.) |
| **Required Properties** | Warn if required properties missing |
| **Google Rich Results** | Eligibility for rich snippets |

### 15.2 Sitemap Validation

| Check | Description |
|-------|-------------|
| **Sitemap Exists** | Verify sitemap.xml is accessible |
| **XML Validity** | Parse and validate structure |
| **URL Coverage** | Pages in sitemap vs pages discovered |
| **URL Accessibility** | All sitemap URLs respond correctly |
| **Last Modified Dates** | Sitemap dates match actual page dates |

---

## 16. Technical Implementation Notes

### 16.1 New Database Models

| Model | Purpose |
|-------|---------|
| `IssueAssignment` | Track issue assignments to users |
| `IssueNote` | Notes on issues (public to team) |
| `IssueStateChange` | Audit trail for Kanban movements |
| `DismissedIssue` | Permanently ignored issues |
| `FalsePositiveReport` | Reported false positives with categories |
| `ScanSchedule` | Automated scan configuration |
| `DiscoveredUrl` | URLs found during scan, pending approval |
| `PageScreenshot` | Stored screenshots per scan |
| `DecorativeImage` | Images marked as decorative (by URL) |
| `BrokenLink` | Detected broken links per scan |
| `DuplicateContent` | Duplicate content findings |
| `PageMetrics` | Core Web Vitals and performance data |
| `SchemaValidation` | Structured data validation results |
| `ProjectLanguageSetting` | Regional spelling configuration |

### 16.2 New Livewire Components

| Component | Purpose |
|-----------|---------|
| `ProjectDashboard` | Main dashboard with cards, grids, table |
| `IssueWorkflow` | Kanban board for issue management |
| `DictionaryPanel` | Dictionary management modal |
| `ScheduleModal` | Scan scheduling configuration |
| `PageDetailView` | Full page analysis view |
| `SocialPreviewAccordion` | Meta/OG display with previews |
| `BulkActionsBar` | Bulk action controls |
| `FilterChips` | Issue type filtering |
| `UrlStatusGrid` | Success/Warning/Error URL display |
| `ReadabilityPanel` | Readability scores display |
| `LinkCheckerResults` | Broken link findings |
| `SchemaViewer` | Structured data display |
| `ContentAnalysisPanel` | Word count, duplicates, freshness |
| `RegionalSettingsForm` | Language/region configuration |

### 16.3 Jobs

| Job | Purpose |
|-----|---------|
| `ProcessScheduledScansJob` | Check and execute scheduled scans |
| `CapturePageScreenshotJob` | Screenshot capture during scan |
| `VerifyFixedIssuesJob` | Post-scan verification of "fixed" issues |
| `GenerateMetaSuggestionsJob` | On-demand AI suggestions |
| `CheckBrokenLinksJob` | Link validation per scan |
| `AnalyzeReadabilityJob` | Readability score calculation |
| `DetectDuplicateContentJob` | Cross-page duplicate detection |
| `ValidateSchemaJob` | Structured data validation |
| `ValidateSitemapJob` | Sitemap validation |
| `MeasureCoreWebVitalsJob` | Performance metrics collection |

### 16.4 Credit Consumption Points

| Action | Credits |
|--------|---------|
| Scan URL | 1 credit per URL |
| AI Meta Suggestions | TBD credits per generation |
| Core Web Vitals measurement | Included in scan |
| Link checking | Included in scan |
| Readability analysis | Included in scan |

---

## 17. Implementation Phases

### Phase 1: Core Dashboard ✅ COMPLETED
- [x] Overview cards with click-to-filter
- [x] Page status grids (Success/Warning/Error)
- [x] Basic findings table with inline suggestions
- [x] Multi-select filter dropdown

### Phase 2: Issue Workflow ✅ COMPLETED
- [x] Full action menu (ignore, dictionary, assign, note, mark fixed)
- [x] Kanban board (4 columns)
- [x] Bulk actions support
- [x] False positive reporting

### Phase 3: Dictionary System ✅ COMPLETED
- [x] Hierarchical dictionary (org + project)
- [x] Conflict resolution UI
- [x] Dictionary management panel

### Phase 4: Scheduled Scans ✅ COMPLETED
- [x] Schedule configuration in project settings
- [x] Credit pre-check before scan
- [x] Email notifications
- [x] New URL discovery and approval

### Phase 5: Meta & Social Previews ✅ COMPLETED
- [x] Meta data extraction and accordion
- [x] Social preview rendering (FB, Twitter, LinkedIn)
- [x] Validation warnings
- [x] On-demand AI suggestions

### Phase 6: Accessibility Checks ✅ COMPLETED
- [x] Alt tag smart detection
- [x] Color contrast checking
- [x] Heading hierarchy analysis
- [x] Form label validation
- [x] Decorative image toggle

### Phase 7: Page Detail View ✅ COMPLETED
- [x] Screenshot capture per scan
- [x] Full page analysis view
- [x] Page load metrics
- [x] Accessibility score overview

### Phase 8: Organization Dashboard ✅ COMPLETED
- [x] Full health dashboard
- [x] Cross-project metrics
- [x] Credit usage tracking
- [x] Scheduled scan calendar

### Phase 9: Export & Polish ✅ COMPLETED
- [x] CSV export
- [x] PDF report generation
- [x] JSON API endpoints
- [x] Email notification templates

### Phase 10: Content Analysis & SEO ✅ COMPLETED
- [x] Regional spelling configuration (US/UK English, etc.)
- [x] Readability scoring (Flesch-Kincaid)
- [x] Word count and thin content warnings
- [x] Duplicate content detection
- [x] Content freshness indicators
- [x] Google Search (SERP) preview

### Phase 11: Technical SEO & Links ✅ COMPLETED
- [x] Broken link detection (internal, external, anchors)
- [x] Schema.org / structured data validation
- [x] Sitemap validation
- [x] Mobile viewport screenshots
- [x] Core Web Vitals integration (LCP, FID, CLS)

---

## Implementation Summary (Completed 2026-02-14)

### Models Created (14)
- IssueNote, IssueStateChange, FalsePositiveReport, DiscoveredUrl, PageScreenshot
- DecorativeImage, PageMetrics, BrokenLink, SchemaValidation, ProjectLanguageSetting
- DuplicateContent, IssueAssignment, ScanSchedule, UrlGroup, DismissedIssue, WebhookIntegration, ScanTemplate

### Livewire Components Created/Enhanced (15+)
- ProjectDashboard (enhanced with overview cards, page grids, findings table, bulk actions)
- IssueWorkflow, IssueKanbanBoard, DictionaryPanel, DictionaryConflictResolver
- ScheduleModal, PageDetailView, SocialPreviewAccordion, FalsePositiveReportModal
- UrlDiscoveryPanel, UrlGroupManager, TrendCharts, BulkImportModal
- OrganizationDashboard, ReportIndex, ProfileEdit, ScanCreate, SettingsIndex
- TeamMembers, TeamDepartments

### Jobs Created (10)
- ProcessScheduledScansJob (enhanced with credit checks)
- CapturePageScreenshotJob, VerifyFixedIssuesJob, CheckBrokenLinksJob
- AnalyzeReadabilityJob, DetectDuplicateContentJob, ValidateSchemaJob
- ValidateSitemapJob, MeasureCoreWebVitalsJob, GenerateMetaSuggestionsJob
- CheckAccessibilityJob

### Services Created
- ExportService (CSV, JSON, PDF exports)
- AccessibilityCheckService (WCAG 2.1 compliance checking)

### Notifications Created (5)
- InsufficientCreditsNotification, ScheduledScanCompletedNotification
- NewIssuesFoundNotification, IssueAssignedNotification, WeeklyReportNotification

### Tests
- 328 tests passing (667 assertions)
- Model tests: DismissedIssue, IssueAssignment, ScanSchedule, DiscoveredUrl
- Livewire tests: OrganizationDashboard, IssueWorkflow + all existing components

---

## 18. Open Questions for Future

1. Full accessibility audit scope and implementation (beyond basic WCAG)
2. Screenshot storage strategy and retention policy
3. PDF report template design
4. API rate limiting and authentication
5. Internationalization for non-Latin character sets
6. JavaScript-rendered content (SPA support)
7. Authentication for scanning password-protected pages
8. Competitive benchmarking data sources
