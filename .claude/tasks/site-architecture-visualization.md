# Site Architecture Visualization - Implementation Plan

## Overview

Enterprise-grade site architecture visualization system that enables users to evaluate internal linking and URL structure through interactive force-directed graphs, tree diagrams, and directory views. Includes comprehensive sitemap generation with exports to Figma, MermaidJS, and PDF formats. Features SEO analysis with AI-powered recommendations, version history tracking, and full integration with existing OnPageIQ features.

**Feature Branch**: `feature/site-architecture-visualization`
**Target**: Paid tiers only
**Status**: Planning

---

## Interview Summary

### Requirements
| Category | Details |
|----------|---------|
| **Use Cases** | SEO Analysis, Content Strategy, Technical Audit |
| **Target Users** | SEO specialists, content strategists, developers, site auditors |
| **Scope** | Full-featured visualization with exports, versioning, and AI insights |
| **Scale** | Support small sites to enterprise (10,000+ pages) |

### Technical Decisions
| Decision | Choice | Rationale |
|----------|--------|-----------|
| **Visualization Library** | D3.js | Maximum flexibility and control |
| **Rendering** | Client-side WebGL/Canvas | Better interactivity, clustering for large graphs |
| **SPA Handling** | Auto-detect | Detect frameworks, auto-enable JS rendering |
| **Link Classification** | Hybrid + manual override | HTML semantics + position analysis |
| **Storage** | Version history | Track architecture changes between scans |

### Constraints
- Paid tiers only (no free tier access)
- Client-side rendering must handle 10,000+ nodes
- Must integrate bidirectionally with existing OnPageIQ features
- D3.js learning curve for complex visualizations

---

## Implementation Phases

### Phase 1: Foundation & Data Model ✅ COMPLETE
- [x] Create feature branch `feature/site-architecture-visualization`
- [x] Create `SiteArchitecture` model (stores crawl snapshots)
- [x] Create `ArchitectureNode` model (individual pages/URLs)
- [x] Create `ArchitectureLink` model (relationships between nodes)
- [x] Create `ArchitectureSnapshot` model (version history)
- [x] Create `ArchitectureIssue` model (SEO/structure issues)
- [x] Create migrations for all models
- [x] Create model factories with realistic test data
- [x] Write model relationship tests (63 tests passing)
- [x] Create `LinkType` enum (navigation, content, footer, sidebar, etc.)
- [x] Create `NodeStatus` enum (ok, redirect, error, orphan, deep)
- [x] Create `ArchitectureStatus` enum (pending, crawling, analyzing, ready, failed)
- [x] Create `ArchitectureIssueType` enum (orphan, deep, broken link, etc.)
- [x] Create `ImpactLevel` enum (critical, serious, moderate, minor)
- [x] Add `siteArchitectures()` and `latestSiteArchitecture()` relationships to Project model

**Implementation Notes:**
- All models use UUIDs via `HasUuids` trait
- Factories include state methods for testing (e.g., `orphan()`, `external()`, `critical()`)
- ArchitectureSnapshot stores full graph state as JSON for version comparison
- ArchitectureIssue has static factory methods: `createOrphanIssue()`, `createDeepPageIssue()`, `createBrokenLinkIssue()`
- Renamed `hasChanges()` to `hasChangesSummary()` to avoid conflict with Eloquent base method

### Phase 2: Crawling & Link Classification ✅ COMPLETE
- [x] Create `ArchitectureCrawlService` - orchestrates crawl
- [x] Create `LinkClassificationService` - hybrid classification
- [x] Create `SpaDetectionService` - auto-detect JS frameworks
- [x] Create `CrawlArchitectureJob` - background crawl job
- [x] Create broadcast events (Progress, Completed, Failed)
- [x] Write service tests with mock HTML fixtures (31 tests)
- [x] Implement robots.txt respect toggle
  - Created `RobotsTxtService` with full robots.txt parsing
  - Supports Allow, Disallow, Crawl-delay, Sitemap directives
  - Handles wildcards (*) and end-of-URL patterns ($)
  - Caches parsed rules per domain (1 hour TTL)
  - Integrated into `ArchitectureCrawlService.shouldCrawl()`
  - 25 tests covering parsing, matching, caching, and error handling
- [x] Implement JavaScript rendering toggle with auto-detect
  - Already implemented via `SpaDetectionService` and `auto_detect_spa` config
  - `fetchPage()` passes `javascript: true` to browser service when enabled
- [x] Implement crawl configuration (depth, patterns, headers, timeouts)
  - Implemented via `CrawlConfigModal` and project-level architecture_config
- [ ] Create `ClassifyLinksJob` - link classification job (deferred)
- [ ] Create `ArchitectureCrawlConfig` model for saved settings (deferred - using project.architecture_config instead)

### Phase 3: Graph Data Processing ✅ COMPLETE
- [x] Create `GraphLayoutService` - compute node positions
- [x] Create `ClusteringService` - group nodes for large graphs
- [x] Create `LinkEquityService` - calculate PageRank-like scores
- [x] Create `OrphanDetectionService` - find orphan pages
- [x] Create `DepthAnalysisService` - analyze crawl depth
- [x] Write service tests (23 tests)
- [x] Implement external link grouping by domain
  - Already implemented in `GraphLayoutService.getD3GraphDataWithExternals()`
  - Groups external links by domain into single external domain nodes
  - Each domain node shows link_count for visual sizing
  - Added 3 tests covering grouping, linking, and edge cases
- [x] Write performance tests for large graphs
  - Created `PerformanceTest.php` with 8 comprehensive tests
  - Tests 1000-node graph handling (< 5 seconds)
  - Tests clustering service with 500 nodes (< 2 seconds)
  - Tests depth analysis scaling
  - Tests link equity calculation convergence (200 nodes, 600 links)
  - Tests orphan detection scaling (400 nodes)
  - Tests external domain handling (50 domains)
  - Tests memory usage (< 50MB for 500 nodes)
  - Tests cache performance (cached retrieval 5x faster)
- [ ] Create `ArchitectureAnalysis` model for cached analysis results (deferred)

### Phase 4: D3.js Visualization Components ✅ COMPLETE
- [x] Create base D3 component wrapper for Livewire
- [x] Create `SiteArchitecturePage` Livewire component with D3.js integration
  - [x] Node rendering with customizable data display
  - [x] Typed edge rendering (colors/styles per link type)
  - [x] Drag interaction for node repositioning
  - [x] Zoom/pan controls
  - [x] Node click for details panel
  - [x] Clustering support via ClusteringService
- [x] Create force-directed graph view via Alpine.js + D3.js
- [x] Create tree diagram view
- [x] Create directory view
- [x] Create shared controls (zoom, reset, fullscreen)
- [x] Write Livewire component tests (21 tests)
- [x] Add route for architecture page

### Phase 5: Interactive Features & UI ✅ COMPLETE
- [x] Create `SiteArchitecturePage` Livewire component (main page) - completed in Phase 4
- [x] Create `NodeDetailPanel` component
  - [x] Page metadata display
  - [x] Inbound/outbound links list
  - [x] Issues for this page
  - [x] Quick actions (view page, run scan)
  - [x] Link equity flow display
- [x] Create `LinkClassificationModal` for manual overrides
  - [x] Link type selection with visual indicators
  - [x] Save and clear override functionality
- [x] Create `CrawlConfigModal` for settings
  - [x] Max depth and max pages configuration
  - [x] Timeout settings
  - [x] Include/exclude URL patterns
  - [x] robots.txt, JS rendering, external links toggles
- [x] Create `ArchitectureFilters` component
  - [x] Filter by link type
  - [x] Filter by depth (min/max)
  - [x] Filter by status
  - [x] Filter by URL pattern
  - [x] Quick filters (orphans, deep, errors)
- [x] Write Livewire component tests (29 tests)

### Phase 6: SEO Analysis & Recommendations ✅ COMPLETE
- [x] Create `ArchitectureSeoService`
  - [x] Orphan page detection
  - [x] Deep page flagging (configurable threshold)
  - [x] Link equity distribution analysis
  - [x] Internal linking opportunities
- [x] Create `ArchitectureRecommendationService`
  - [x] AI-powered improvement suggestions
  - [x] Priority scoring for recommendations
  - [x] Actionable fix suggestions
- [x] Create `SeoInsightsPanel` component
  - [x] Summary statistics
  - [x] Issue breakdown by type
  - [x] Recommendations list
- [x] Create `ArchitectureIssue` model (completed in Phase 1)
- [x] Integrate architecture issues with main findings table (completed in Phase 10)
- [x] Write SEO analysis tests (18 tests)

### Phase 7: Version History & Comparison ✅ COMPLETE
- [x] Implement snapshot creation on each crawl (via ArchitectureSnapshot::createFromArchitecture)
- [x] Create `ArchitectureComparisonService`
  - [x] Diff calculation (added, removed, changed)
  - [x] Change categorization (expansion, contraction, restructuring, etc.)
  - [x] Timeline generation
  - [x] Retention policy application
  - [x] Report generation with highlights
- [x] Create `ComparisonView` component
  - [x] Side-by-side mode
  - [x] Overlay diff mode (highlight changes)
  - [x] Timeline slider mode
  - [x] Filter controls (show/hide added, removed, changed, unchanged)
- [x] Create `VersionHistoryPanel` component
  - [x] Snapshot list with timestamps
  - [x] Quick compare between any two versions
  - [x] Change summary per version
  - [x] Snapshot deletion with confirmation
- [x] Implement snapshot retention policy
- [x] Write comparison tests (27 tests)

### Phase 8: Sitemap Generation ✅ COMPLETE
- [x] Create `SitemapGeneratorService`
  - [x] XML sitemap generation (sitemap.xml)
  - [x] Auto-calculate priority values
  - [x] Auto-calculate change frequency
  - [x] Support for sitemap index (large sites)
- [x] Create `VisualSitemapService`
  - [x] Generate visual sitemap structure
  - [x] Support hierarchical layout
  - [x] D3 data format generation
  - [x] Section grouping
- [x] Create `HtmlSitemapService`
  - [x] Generate user-facing HTML sitemap
  - [x] Categorized page listing
  - [x] Hierarchical HTML layout
- [x] Create `SitemapValidationService`
  - [x] Validate against existing sitemap.xml
  - [x] Identify missing/extra URLs
  - [x] Health score calculation
  - [x] Validation reports
- [x] Create `SitemapPanel` component
- [x] Write sitemap generation tests (27 tests)

### Phase 9: Export System ✅ COMPLETE
- [x] Create `ExportService` base class
- [x] Create `SvgExportService`
  - [x] Export current view as SVG
  - [x] Include legend and metadata
  - [x] Hierarchical node positioning by depth
  - [x] Status-based color coding
- [x] Create `FigmaExportService`
  - [x] Figma JSON import format
  - [x] Node frames with badges
  - [x] Bezier connection lines
  - [x] Legend and title frames
- [x] Create `MermaidExportService`
  - [x] Flowchart syntax generation
  - [x] Mindmap syntax generation
  - [x] Graph syntax generation
  - [x] Depth-based subgraphs
- [x] Create `PdfExportService`
  - [x] Configurable sections (cover, TOC, statistics, nodes, recommendations)
  - [x] Health score calculation
  - [x] Statistics with depth distribution
  - [x] AI-generated recommendations
  - [x] Branded templates with custom colors
- [x] Create `ExportConfigModal` Livewire component
  - [x] Format selection (SVG, Mermaid, Figma, PDF)
  - [x] Format-specific options
  - [x] Common options (include errors, external links)
- [x] Create `ExportArchitectureJob` for async exports
- [x] Create `ExportCompleted` broadcast event
- [x] Write export tests (45 tests)

**Implementation Notes:**
- `ExportService` is abstract base class with `getNodes()`, `getLinks()`, `getMetadata()`, `getTitleFromPath()` methods
- External links filtered at `getLinks()` level (not nodes) since `external_domain` is on `ArchitectureLink` model
- PDF uses DomPDF with Blade template `exports/architecture-report.blade.php`
- Export job stores files with `.meta.json` for tracking and auto-cleanup
- Activity logging is optional (checks for spatie/laravel-activitylog availability)

### Phase 10: Integration & Polish ✅ COMPLETE
- [x] Add "Site Architecture" to project navigation
  - Created `x-projects.navigation` component with Dashboard, Architecture, Dictionary tabs
  - Added to `site-architecture-page.blade.php`, `project-dashboard.blade.php`, `project-dictionary.blade.php`
  - Removed duplicate breadcrumbs from individual pages
- [x] Add keyboard shortcuts for navigation
  - `1`, `2`, `3` - Switch between Force Graph, Tree, Directory views
  - `E` - Toggle external links
  - `C` - Toggle clusters
  - `Escape` - Clear selection / close modals
  - `?` - Show keyboard shortcuts help modal
- [x] Add accessibility features (ARIA labels, keyboard nav)
  - Added `role="main"` and `aria-label` to main container
  - Added `aria-pressed` to view mode toggle buttons
  - Added `aria-label` to all zoom control buttons
  - Added `aria-describedby` to toggle checkboxes
  - Added `role="group"` with `aria-labelledby` to button groups
  - Added focus ring styles for keyboard navigation
  - Created accessible keyboard shortcuts help modal with `role="dialog"` and `aria-modal`
- [x] Write integration tests
  - Created `tests/Feature/Livewire/Projects/ProjectNavigationTest.php` (13 tests)
  - Tests for navigation rendering on all project pages
  - Tests for accessibility attributes on architecture page
  - Tests for keyboard shortcut hints and help modal
- [x] Add architecture issues to main findings
  - Added `getArchitectureIssues()` method to ProjectDashboard.php
  - Normalized ArchitectureIssue objects to match Issue structure for unified display
  - Merged architecture issues with content issues in `findings()` computed property
  - Added architecture count to `findingsCounts()` for filter chips
  - Added "Architecture" filter chip to findings table
  - Added architecture-specific actions: "View in Graph", "Resolve Issue", "Dismiss Issue"
  - Implemented `resolveArchitectureIssue()` and `ignoreArchitectureIssue()` methods
  - Page column links architecture issues directly to the architecture visualization page
- [x] Add architecture link to page detail view
  - Added `architectureNode()` computed property to `PageDetailView.php`
  - Looks up node in latest architecture by matching URL
  - Added "View in Architecture" button in page header (indigo styling)
  - Created `PageDetailViewTest.php` with 7 tests
- [x] Integrate with existing crawl/scan data
  - Added `use_existing_scans` config option (default: true)
  - Added `scan_data_max_age_hours` config option (default: 24 hours)
  - Added `preloadExistingScans()` method to preload URLs with recent scan results
  - Added `getExistingScanData()` method to retrieve cached HTML from scans
  - Modified `fetchPage()` to check for existing scan data before making HTTP requests
  - Reuses `content_snapshot` from ScanResult to avoid redundant page fetches
- [x] Implement real-time crawl progress updates
  - Added `broadcastProgress()` method to ArchitectureCrawlService (throttled every 5 pages)
  - Added progress tracking properties to SiteArchitecturePage (isCrawling, crawledPages, etc.)
  - Added `getListeners()` for Reverb broadcast events (Progress, Completed, Failed)
  - Added `handleCrawlProgress()`, `handleCrawlCompleted()`, `handleCrawlFailed()` handlers
  - Added crawl progress indicator UI with animated spinner, progress bar, and current URL
  - Uses existing `ArchitectureCrawlProgress` event to broadcast via Reverb
- [x] Create project settings for architecture defaults
  - Created migration `add_architecture_config_to_projects_table` for JSON config column
  - Added `architecture_config` to Project model $fillable and casts
  - Added `getArchitectureConfigWithDefaults()` method with sensible defaults
  - Updated `CrawlConfigModal` to load saved project defaults on open
  - Added "Save as project defaults" checkbox to modal UI
  - Implemented `saveProjectDefaults()` to persist config on crawl start
  - Added 3 tests for loading/saving project config defaults
- [x] Performance optimization for large graphs
  - Added caching to `GraphLayoutService.getD3GraphData()` (5 min TTL)
  - Added caching to `GraphLayoutService.getD3GraphDataWithExternals()`
  - Added `clearCache()` method for manual cache invalidation
  - Auto-clears cache when `SiteArchitecture` model is updated
  - Added 3 tests covering caching behavior
  - Existing indexes on architecture tables provide query optimization
- [ ] Create user documentation (deferred)

**Implementation Notes:**
- Fixed bug in `OrphanDetectionService::detectOrphans()` - closure needed `&$orphans` reference
- Fixed pre-existing bug in `IssueAssignmentTest` - test expected 'open' but factory randomized status
- Architecture issues displayed with indigo color scheme (bg-indigo-100 text-indigo-700)
- Synthetic issue objects created with `is_architecture_issue` flag for template conditional rendering
- All 643 tests passing (1428 assertions)
- Fixed orphan factory to set non-zero depth (avoid homepage misdetection)

---

## Key Files to Create/Modify

### Models
| Model | Purpose |
|-------|---------|
| `SiteArchitecture` | Main architecture record per project |
| `ArchitectureNode` | Individual page/URL in the graph |
| `ArchitectureLink` | Link relationship between nodes |
| `ArchitectureSnapshot` | Version history snapshot |
| `ArchitectureIssue` | SEO/structure issues found |
| `ArchitectureCrawlConfig` | Saved crawl configurations |
| `ArchitectureSavedView` | User's saved custom views |

### Enums
| Enum | Values |
|------|--------|
| `LinkType` | Navigation, Content, Footer, Sidebar, Header, Breadcrumb, Pagination, External |
| `NodeStatus` | Ok, Redirect, ClientError, ServerError, Timeout, Orphan, Deep |
| `ArchitectureIssueType` | OrphanPage, DeepPage, BrokenLink, RedirectChain, LowLinkEquity, MissingFromSitemap |

### Services
| Service | Purpose |
|---------|---------|
| `ArchitectureCrawlService` | Orchestrate architecture crawling |
| `LinkClassificationService` | Classify links by type (nav, content, etc.) |
| `SpaDetectionService` | Detect JavaScript frameworks |
| `GraphLayoutService` | Compute force-directed layout positions |
| `ClusteringService` | Group nodes for large graph performance |
| `LinkEquityService` | Calculate PageRank-like scores |
| `OrphanDetectionService` | Find pages with no inbound links |
| `DepthAnalysisService` | Analyze crawl depth from homepage |
| `ArchitectureSeoService` | SEO analysis and issue detection |
| `ArchitectureRecommendationService` | AI-powered recommendations |
| `ArchitectureComparisonService` | Compare snapshots, generate diffs |
| `SitemapGeneratorService` | Generate XML sitemaps |
| `VisualSitemapService` | Generate visual sitemap layouts |
| `HtmlSitemapService` | Generate HTML sitemap pages |
| `SvgExportService` | Export graphs as SVG |
| `FigmaExportService` | Export to Figma formats |
| `MermaidExportService` | Export as MermaidJS syntax |
| `PdfExportService` | Generate PDF reports |

### Livewire Components
| Component | Purpose |
|-----------|---------|
| `SiteArchitecturePage` | Main architecture visualization page |
| `ForceDirectedGraph` | D3.js force-directed graph visualization |
| `TreeDiagram` | Hierarchical tree visualization |
| `DirectoryView` | Folder-based directory visualization |
| `ArchitectureToolbar` | View controls, filters, export |
| `NodeDetailPanel` | Selected node information panel |
| `SeoInsightsPanel` | SEO analysis and recommendations |
| `ComparisonView` | Version comparison visualizations |
| `VersionHistoryPanel` | Snapshot history and selection |
| `SitemapPanel` | Sitemap generation and validation |
| `CrawlConfigModal` | Crawl settings configuration |
| `ExportConfigModal` | Export options configuration |
| `LinkClassificationModal` | Manual link type override |
| `ArchitectureFilters` | Filter controls for graph |

### Jobs
| Job | Purpose |
|-----|---------|
| `CrawlArchitectureJob` | Background architecture crawl |
| `ClassifyLinksJob` | Classify links after crawl |
| `AnalyzeArchitectureJob` | Run SEO analysis |
| `GenerateRecommendationsJob` | Generate AI recommendations |
| `CreateSnapshotJob` | Create version snapshot |
| `ExportArchitectureJob` | Async export generation |
| `GenerateSitemapJob` | Generate sitemap files |

### Migrations
- `create_site_architectures_table`
- `create_architecture_nodes_table`
- `create_architecture_links_table`
- `create_architecture_snapshots_table`
- `create_architecture_issues_table`
- `create_architecture_crawl_configs_table`
- `create_architecture_saved_views_table`

---

## Database Schema

### site_architectures
| Column | Type | Description |
|--------|------|-------------|
| id | uuid | Primary key |
| project_id | uuid | FK to projects |
| status | enum | pending, crawling, analyzing, ready, failed |
| total_nodes | int | Total pages discovered |
| total_links | int | Total links discovered |
| max_depth | int | Maximum crawl depth reached |
| orphan_count | int | Pages with no inbound links |
| error_count | int | Pages with errors |
| crawl_config | json | Crawl configuration used |
| last_crawled_at | timestamp | When last crawl completed |
| created_at | timestamp | |
| updated_at | timestamp | |

### architecture_nodes
| Column | Type | Description |
|--------|------|-------------|
| id | uuid | Primary key |
| site_architecture_id | uuid | FK to site_architectures |
| url | string | Full URL |
| path | string | URL path only |
| title | string | Page title |
| status | enum | ok, redirect, client_error, server_error, timeout |
| http_status | int | HTTP status code |
| depth | int | Clicks from homepage |
| inbound_count | int | Number of inbound links |
| outbound_count | int | Number of outbound links |
| link_equity_score | decimal | PageRank-like score |
| word_count | int | Page word count |
| issues_count | int | Number of issues |
| is_orphan | boolean | No inbound internal links |
| is_deep | boolean | Exceeds depth threshold |
| metadata | json | Additional page metadata |
| position_x | decimal | Cached graph X position |
| position_y | decimal | Cached graph Y position |
| created_at | timestamp | |
| updated_at | timestamp | |

### architecture_links
| Column | Type | Description |
|--------|------|-------------|
| id | uuid | Primary key |
| site_architecture_id | uuid | FK to site_architectures |
| source_node_id | uuid | FK to source node |
| target_node_id | uuid | FK to target node |
| link_type | enum | navigation, content, footer, sidebar, etc. |
| link_type_override | enum | Manual override if set |
| anchor_text | string | Link anchor text |
| is_external | boolean | Links to external domain |
| external_domain | string | External domain if applicable |
| is_nofollow | boolean | Has nofollow attribute |
| position_in_page | enum | header, body, footer, sidebar |
| created_at | timestamp | |

### architecture_snapshots
| Column | Type | Description |
|--------|------|-------------|
| id | uuid | Primary key |
| site_architecture_id | uuid | FK to site_architectures |
| snapshot_data | json | Full graph state |
| nodes_count | int | Nodes at snapshot time |
| links_count | int | Links at snapshot time |
| changes_summary | json | Summary of changes from previous |
| created_at | timestamp | Snapshot timestamp |

### architecture_issues
| Column | Type | Description |
|--------|------|-------------|
| id | uuid | Primary key |
| site_architecture_id | uuid | FK to site_architectures |
| node_id | uuid | FK to affected node |
| issue_type | enum | orphan_page, deep_page, broken_link, etc. |
| severity | enum | critical, serious, moderate, minor |
| message | text | Issue description |
| recommendation | text | AI-generated recommendation |
| is_resolved | boolean | Marked as resolved |
| created_at | timestamp | |
| updated_at | timestamp | |

---

## API Endpoints

```
# Architecture management
POST   /api/v1/projects/{project}/architecture/crawl
GET    /api/v1/projects/{project}/architecture
GET    /api/v1/projects/{project}/architecture/nodes
GET    /api/v1/projects/{project}/architecture/links
GET    /api/v1/projects/{project}/architecture/issues

# Snapshots
GET    /api/v1/projects/{project}/architecture/snapshots
GET    /api/v1/projects/{project}/architecture/snapshots/{snapshot}
GET    /api/v1/projects/{project}/architecture/compare/{snapshot1}/{snapshot2}

# Exports
POST   /api/v1/projects/{project}/architecture/export/svg
POST   /api/v1/projects/{project}/architecture/export/figma
POST   /api/v1/projects/{project}/architecture/export/mermaid
POST   /api/v1/projects/{project}/architecture/export/pdf
POST   /api/v1/projects/{project}/architecture/export/sitemap

# Saved views
GET    /api/v1/projects/{project}/architecture/views
POST   /api/v1/projects/{project}/architecture/views
PUT    /api/v1/projects/{project}/architecture/views/{view}
DELETE /api/v1/projects/{project}/architecture/views/{view}
```

---

## D3.js Visualization Specifications

### Force-Directed Graph

```javascript
// Node sizing by metric (configurable)
nodeRadius = d3.scaleSqrt()
  .domain([0, maxLinkEquity])
  .range([5, 30]);

// Edge styling by type
linkColors = {
  navigation: '#3B82F6',  // blue
  content: '#10B981',     // green
  footer: '#6B7280',      // gray
  sidebar: '#8B5CF6',     // purple
  external: '#F59E0B'     // amber
};

// Clustering for large graphs
forceCluster = d3.forceCluster()
  .centers(clusterCenters)
  .strength(0.5);
```

### Tree Diagram

```javascript
// URL path hierarchy
pathTree = d3.stratify()
  .id(d => d.path)
  .parentId(d => getParentPath(d.path));

// Crawl depth hierarchy
depthTree = d3.stratify()
  .id(d => d.id)
  .parentId(d => d.parentNodeId);
```

---

## Testing Strategy

### Unit Tests
- [ ] Model relationship tests
- [ ] Service method tests
- [ ] Link classification accuracy tests
- [ ] PageRank calculation tests
- [ ] Sitemap generation tests

### Feature Tests
- [ ] Crawl job execution tests
- [ ] Export generation tests
- [ ] API endpoint tests
- [ ] Livewire component tests

### Performance Tests
- [x] Graph rendering with 1,000 nodes
- [ ] Graph rendering with 10,000 nodes (deferred - requires WebGL)
- [x] Clustering effectiveness tests
- [ ] Export generation time tests (deferred)

### Manual Verification
1. Crawl a real site with known structure
2. Verify link classification accuracy
3. Test all visualization modes
4. Export to all formats
5. Compare snapshots between crawls
6. Verify SEO recommendations accuracy

---

## Verification Commands

```bash
# Run all architecture tests
php artisan test --filter=Architecture

# Run specific test groups
php artisan test tests/Feature/Architecture/
php artisan test tests/Unit/Services/Architecture/

# Verify crawl functionality
php artisan tinker
>>> $project = Project::first();
>>> CrawlArchitectureJob::dispatch($project);

# Check graph data
>>> $arch = SiteArchitecture::first();
>>> $arch->nodes()->count();
>>> $arch->links()->count();
```

---

## Implementation Checklist

### Phase 1: Foundation ✅ COMPLETE
- [x] Create feature branch
- [x] Create all models with factories
- [x] Create all migrations
- [x] Create enums
- [x] Write model tests (63 tests)
- [x] Run migrations

### Phase 2: Crawling ✅ COMPLETE
- [x] Create crawl services
- [x] Create classification service
- [x] Create SPA detection
- [x] Create crawl job
- [x] Write crawl tests (31 tests)
- [ ] Test with real sites (manual verification)

### Phase 3: Graph Processing ✅ COMPLETE
- [x] Create layout service
- [x] Create clustering service
- [x] Create SEO services (orphan, depth, link equity)
- [x] Write analysis tests (23 tests)
- [x] Performance benchmarks (8 tests)

### Phase 4: Visualizations ✅ COMPLETE
- [x] Set up D3.js integration
- [x] Create force-directed graph
- [x] Create tree diagram
- [x] Create directory view
- [x] Write component tests (21 tests)

### Phase 5: UI ✅ COMPLETE
- [x] Create main page (completed in Phase 4)
- [x] Create detail panel (NodeDetailPanel)
- [x] Create filters (ArchitectureFilters)
- [x] Create modals (CrawlConfigModal, LinkClassificationModal)
- [x] Write UI tests (29 tests)

### Phase 6: SEO ✅ COMPLETE
- [x] Implement analysis (ArchitectureSeoService)
- [x] Add AI recommendations (ArchitectureRecommendationService)
- [x] Create SeoInsightsPanel component
- [x] Write SEO tests (18 tests)

### Phase 7: Versioning ✅ COMPLETE
- [x] Implement ArchitectureComparisonService
- [x] Create VersionHistoryPanel and ComparisonView components
- [x] Write comparison tests (27 tests)

### Phase 8: Sitemaps ✅ COMPLETE
- [x] XML generation (SitemapGeneratorService)
- [x] Visual sitemap (VisualSitemapService)
- [x] HTML sitemap (HtmlSitemapService)
- [x] Validation (SitemapValidationService)
- [x] Write sitemap tests (27 tests)

### Phase 9: Exports ✅ COMPLETE
- [x] SVG export (SvgExportService)
- [x] Figma integration (FigmaExportService)
- [x] MermaidJS export (MermaidExportService)
- [x] PDF generation (PdfExportService)
- [x] Write export tests (45 tests)

### Phase 10: Polish ✅ COMPLETE
- [x] Navigation integration
- [x] Keyboard shortcuts
- [x] Accessibility features
- [x] Architecture issues in findings
- [x] Real-time crawl progress updates
- [x] Project settings for architecture defaults
- [x] Performance optimization for large graphs (caching + tests)
- [ ] User documentation (deferred)

---

## Notes

- D3.js requires careful memory management for large graphs
- WebGL fallback needed for 1000+ nodes
- Figma API has rate limits - implement queuing
- PDF generation may need server-side rendering for large graphs
- Consider lazy loading for snapshot comparison data
- Link equity algorithm should be configurable
- External link grouping helps reduce visual clutter

---

## Dependencies

### Composer Packages
- None new required (uses existing Browsershot/Playwright)

### NPM Packages
- `d3` - Core visualization library
- `d3-force` - Force simulation
- `d3-hierarchy` - Tree layouts
- `d3-zoom` - Zoom/pan interactions
- `topojson-client` - For clustering (optional)

---

## Progress Log

### 2026-02-15 - Phase 1 Complete
- Created feature branch `feature/site-architecture-visualization`
- Created 5 models: SiteArchitecture, ArchitectureNode, ArchitectureLink, ArchitectureSnapshot, ArchitectureIssue
- Created 5 enums: LinkType, NodeStatus, ArchitectureStatus, ArchitectureIssueType, ImpactLevel
- Created 5 migrations with full schema
- Created 5 factories with comprehensive state methods
- Added relationships to Project model
- Wrote 63 model tests (all passing)
- Files created:
  - `app/Models/SiteArchitecture.php`
  - `app/Models/ArchitectureNode.php`
  - `app/Models/ArchitectureLink.php`
  - `app/Models/ArchitectureSnapshot.php`
  - `app/Models/ArchitectureIssue.php`
  - `app/Enums/LinkType.php`
  - `app/Enums/NodeStatus.php`
  - `app/Enums/ArchitectureStatus.php`
  - `app/Enums/ArchitectureIssueType.php`
  - `app/Enums/ImpactLevel.php`
  - `database/migrations/2026_02_15_162620_create_site_architectures_table.php`
  - `database/migrations/2026_02_15_162621_create_architecture_nodes_table.php`
  - `database/migrations/2026_02_15_162622_create_architecture_links_table.php`
  - `database/migrations/2026_02_15_162622_create_architecture_snapshots_table.php`
  - `database/migrations/2026_02_15_162623_create_architecture_issues_table.php`
  - `tests/Feature/SiteArchitecture/SiteArchitectureModelTest.php`

### 2026-02-15 - Phase 3 Complete
- Created 5 graph processing services:
  - `OrphanDetectionService` - Detects orphan pages with no inbound links, calculates orphan rate, suggests linking opportunities
  - `DepthAnalysisService` - BFS-based depth analysis, deep page detection, depth scoring with grades
  - `LinkEquityService` - PageRank-like algorithm with configurable damping factor, equity flow analysis
  - `GraphLayoutService` - Force-directed, hierarchical, radial, and circular layouts, D3.js data export
  - `ClusteringService` - Clustering by path, depth, content type, and link density
- Wrote 23 service tests (all passing)
- Total tests: 117 passing
- Files created:
  - `app/Services/Architecture/OrphanDetectionService.php`
  - `app/Services/Architecture/DepthAnalysisService.php`
  - `app/Services/Architecture/LinkEquityService.php`
  - `app/Services/Architecture/GraphLayoutService.php`
  - `app/Services/Architecture/ClusteringService.php`
  - `tests/Feature/SiteArchitecture/GraphProcessingServicesTest.php`

### 2026-02-15 - Phase 2 Complete
- Created 3 services for crawling and classification:
  - `LinkClassificationService` - Classifies links by semantic context (nav, header, footer, aside), CSS patterns, ARIA roles
  - `SpaDetectionService` - Detects JS frameworks (React, Vue, Angular, Svelte, Next, Nuxt, Gatsby, Ember)
  - `ArchitectureCrawlService` - Orchestrates breadth-first crawl with configurable depth
- Created `CrawlArchitectureJob` for background processing (1 hour timeout)
- Created 3 broadcast events for real-time progress updates:
  - `ArchitectureCrawlProgress` - Broadcasts crawl progress
  - `ArchitectureCrawlCompleted` - Broadcasts completion
  - `ArchitectureCrawlFailed` - Broadcasts failures
- Wrote 31 service tests (all passing)
- Fixed SPA detection for:
  - Empty root div detection (DOM-based check for whitespace variations)
  - SSR content threshold (250 chars for requires_js_rendering)
  - Bundler artifact detection scoring (increased from 15 to 25 points)
- Files created:
  - `app/Services/Architecture/LinkClassificationService.php`
  - `app/Services/Architecture/SpaDetectionService.php`
  - `app/Services/Architecture/ArchitectureCrawlService.php`
  - `app/Jobs/CrawlArchitectureJob.php`
  - `app/Events/ArchitectureCrawlProgress.php`
  - `app/Events/ArchitectureCrawlCompleted.php`
  - `app/Events/ArchitectureCrawlFailed.php`
  - `tests/Feature/SiteArchitecture/LinkClassificationServiceTest.php`
  - `tests/Feature/SiteArchitecture/SpaDetectionServiceTest.php`

### 2026-02-15 - Phase 5 Complete
- Created 4 Livewire components for interactive UI:
  - `NodeDetailPanel` - Displays selected node metadata, links, issues, and equity flow
  - `CrawlConfigModal` - Configures crawl settings (depth, pages, timeout, patterns, toggles)
  - `ArchitectureFilters` - Filter by depth range, link type, status, URL pattern, quick filters
  - `LinkClassificationModal` - Manual link type override with visual selection
- Wrote 29 Livewire component tests (all passing)
- Total tests: 167 passing
- Files created:
  - `app/Livewire/SiteArchitecture/NodeDetailPanel.php`
  - `app/Livewire/SiteArchitecture/CrawlConfigModal.php`
  - `app/Livewire/SiteArchitecture/ArchitectureFilters.php`
  - `app/Livewire/SiteArchitecture/LinkClassificationModal.php`
  - `resources/views/livewire/site-architecture/node-detail-panel.blade.php`
  - `resources/views/livewire/site-architecture/crawl-config-modal.blade.php`
  - `resources/views/livewire/site-architecture/architecture-filters.blade.php`
  - `resources/views/livewire/site-architecture/link-classification-modal.blade.php`
  - `tests/Feature/SiteArchitecture/Phase5ComponentsTest.php`

### 2026-02-15 - Phase 4 Complete
- Created main SiteArchitecturePage Livewire component with D3.js integration
- Implemented three view modes: force-directed, tree, directory
- Created Alpine.js-based D3 visualization component with:
  - Force simulation with configurable parameters
  - Node rendering with status-based colors and size by link equity
  - Edge rendering with link type colors
  - Zoom/pan controls and reset functionality
  - Node selection with detail panel
  - Cluster visualization toggle
  - External links toggle
  - Legend display
- Added route: `projects/{project}/architecture`
- Wrote 21 Livewire component tests (all passing)
- Total tests: 138 passing
- Files created:
  - `app/Livewire/SiteArchitecture/SiteArchitecturePage.php`
  - `resources/views/livewire/site-architecture/site-architecture-page.blade.php`
  - `tests/Feature/SiteArchitecture/SiteArchitecturePageTest.php`
- Files modified:
  - `routes/web.php` - Added architecture route

### 2026-02-15 - Phase 8 Complete
- Created 4 sitemap services:
  - `SitemapGeneratorService` - XML sitemap generation with priority/changefreq calculation, sitemap index support for large sites
  - `VisualSitemapService` - Hierarchical/section-based sitemap structures, D3 data format, breadcrumbs
  - `HtmlSitemapService` - User-facing HTML sitemaps with sections or hierarchical layout
  - `SitemapValidationService` - Validate sitemaps against architecture, detect stale/missing URLs, health scoring
- Created `SitemapPanel` Livewire component:
  - Generate tab (XML/HTML with stats and preview)
  - Validate tab (URL or paste content)
  - Visual tab (structure stats and sections)
- Wrote 27 tests (all passing)
- Total tests: 239+ passing
- Files created:
  - `app/Services/Architecture/SitemapGeneratorService.php`
  - `app/Services/Architecture/VisualSitemapService.php`
  - `app/Services/Architecture/HtmlSitemapService.php`
  - `app/Services/Architecture/SitemapValidationService.php`
  - `app/Livewire/SiteArchitecture/SitemapPanel.php`
  - `resources/views/livewire/site-architecture/sitemap-panel.blade.php`
  - `tests/Feature/SiteArchitecture/SitemapGenerationTest.php`

### 2026-02-15 - Phase 7 Complete
- Created `ArchitectureComparisonService`:
  - Full diff calculation (nodes added, removed, changed)
  - Change categorization (expansion, contraction, net_growth, net_shrinkage, restructuring)
  - Timeline generation with change summaries
  - Retention policy implementation (min/max snapshots, max age)
  - Report generation with highlights
  - Metric comparison with percentage changes
- Created 2 Livewire components:
  - `VersionHistoryPanel` - Snapshot list, selection, comparison mode, deletion
  - `ComparisonView` - Side-by-side, overlay diff, timeline slider modes
- Wrote 27 tests (all passing)
- Total tests: 212+ passing
- Files created:
  - `app/Services/Architecture/ArchitectureComparisonService.php`
  - `app/Livewire/SiteArchitecture/VersionHistoryPanel.php`
  - `app/Livewire/SiteArchitecture/ComparisonView.php`
  - `resources/views/livewire/site-architecture/version-history-panel.blade.php`
  - `resources/views/livewire/site-architecture/comparison-view.blade.php`
  - `tests/Feature/SiteArchitecture/VersionComparisonTest.php`

### 2026-02-15 - Phase 6 Complete
- Created 2 SEO analysis services:
  - `ArchitectureSeoService` - Full SEO analysis with orphan detection, depth analysis, equity distribution, linking opportunities, issue creation, and overall scoring
  - `ArchitectureRecommendationService` - Prioritized recommendations with effort estimates, fix roadmaps (quick wins, major projects, fill-ins, deprioritize), and summaries
- Created `SeoInsightsPanel` Livewire component:
  - Tab-based interface (overview, issues, recommendations, roadmap)
  - Reactive computed properties for analysis data
  - Integration with both SEO services
- Wrote 18 SEO analysis tests (all passing)
- Total tests: 185+ passing
- Files created:
  - `app/Services/Architecture/ArchitectureSeoService.php`
  - `app/Services/Architecture/ArchitectureRecommendationService.php`
  - `app/Livewire/SiteArchitecture/SeoInsightsPanel.php`
  - `resources/views/livewire/site-architecture/seo-insights-panel.blade.php`
  - `tests/Feature/SiteArchitecture/SeoAnalysisTest.php`

### 2026-02-15 - Phase 10 Architecture Issues Integration
- Integrated architecture issues with project dashboard findings table
- Modified `app/Livewire/Projects/ProjectDashboard.php`:
  - Added `getArchitectureIssues()` helper method to fetch and normalize architecture issues
  - Updated `findings()` to merge architecture issues with content issues
  - Updated `findingsCounts()` to include architecture issue count
  - Added `resolveArchitectureIssue()` method to mark issues as resolved
  - Added `ignoreArchitectureIssue()` method to dismiss issues
- Modified `resources/views/livewire/projects/project-dashboard.blade.php`:
  - Added "Architecture" filter chip with indigo color scheme
  - Added architecture-specific page column handling (links to architecture page)
  - Added architecture issue type display in Type column
  - Added architecture-specific actions dropdown (View in Graph, Resolve, Dismiss)
- All 628 tests passing (including 303 Architecture tests)

### 2026-02-15 - Phase 10 Performance Optimization Complete
- Added caching to GraphLayoutService for graph data
- Cache TTL: 5 minutes for computed graph data
- `getD3GraphData()` and `getD3GraphDataWithExternals()` now use caching
- Added `clearCache()` method for manual invalidation
- SiteArchitecture model auto-clears cache on update via `booted()` method
- Added 3 tests for caching behavior
- All 677 tests passing (1502 assertions)
- Files modified:
  - `app/Services/Architecture/GraphLayoutService.php`
  - `app/Models/SiteArchitecture.php`
  - `tests/Feature/SiteArchitecture/GraphProcessingServicesTest.php`

### 2026-02-15 - Phase 3 External Link Grouping Tests Added
- Added 3 tests for external link grouping by domain functionality
- Tests cover: domain grouping, link creation to domain nodes, empty case
- Functionality was already implemented in `GraphLayoutService.getD3GraphDataWithExternals()`
- All 674 tests passing (1497 assertions)

### 2026-02-15 - Phase 2 Robots.txt Implementation Complete
- Created `RobotsTxtService` for parsing and checking robots.txt rules
- Full robots.txt spec support:
  - User-agent matching (wildcard and specific)
  - Disallow and Allow directives
  - Wildcard patterns (*) and end-of-URL patterns ($)
  - Crawl-delay directive
  - Sitemap directive extraction
- Integrated into `ArchitectureCrawlService`:
  - Pre-fetches robots.txt on crawl start when `respect_robots_txt` is enabled
  - `shouldCrawl()` checks `RobotsTxtService.isAllowed()` before crawling
- Caching with 1-hour TTL to avoid repeated fetches
- Graceful error handling (allows crawling if robots.txt unavailable)
- Created 25 comprehensive tests in `RobotsTxtServiceTest.php`
- All 334 architecture tests passing (799 assertions)
- Files created:
  - `app/Services/Architecture/RobotsTxtService.php`
  - `tests/Feature/SiteArchitecture/RobotsTxtServiceTest.php`
- Files modified:
  - `app/Services/Architecture/ArchitectureCrawlService.php`

### 2026-02-15 - Phase 10 Project Settings Complete
- Created `add_architecture_config_to_projects_table` migration
- Added `architecture_config` JSON column to projects table
- Added `getArchitectureConfigWithDefaults()` method to Project model
- Updated CrawlConfigModal to load project defaults on open via `loadProjectDefaults()`
- Added "Save as project defaults" checkbox to crawl-config-modal.blade.php
- Implemented `saveProjectDefaults()` to persist config when checkbox is checked
- Added 3 new tests for loading/saving project config defaults
- All 309 architecture tests passing (755 assertions)
- Files modified:
  - `database/migrations/2026_02_15_182917_add_architecture_config_to_projects_table.php`
  - `app/Models/Project.php`
  - `app/Livewire/SiteArchitecture/CrawlConfigModal.php`
  - `resources/views/livewire/site-architecture/crawl-config-modal.blade.php`
  - `tests/Feature/SiteArchitecture/Phase5ComponentsTest.php`

### 2026-02-15 - Phase 3 Performance Tests Complete
- Created `PerformanceTest.php` with 8 comprehensive performance tests
- Tests cover:
  - 1000-node graph data generation (< 5 seconds)
  - Clustering service with 500 nodes by varied paths (< 2 seconds)
  - Depth analysis with 501 nodes across 6 depth levels
  - Link equity PageRank convergence (200 nodes, 600 random links)
  - Orphan detection with actual inbound links (300 linked + 100 orphan nodes)
  - External domain grouping (100 internal nodes + 50 external domains)
  - Memory usage limits (< 50MB for 500 nodes)
  - Cache performance (5x faster on cached retrieval)
- Fixed test assertions to match actual service return structures
- All 326 SiteArchitecture tests passing (787 assertions)
- Files created:
  - `tests/Feature/SiteArchitecture/PerformanceTest.php`

### [Date] - Planning Complete
- Conducted comprehensive interview
- Defined all phases and tasks
- Created database schema
- Specified D3.js visualization requirements
- Ready for implementation approval
