<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>{{ $metadata['project_name'] }} - Site Architecture Report</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 12px;
            line-height: 1.5;
            color: #1f2937;
            padding: 40px;
        }

        .brand-color { color: {{ $options['brand_color'] }}; }
        .brand-bg { background-color: {{ $options['brand_color'] }}; }
        .brand-border { border-color: {{ $options['brand_color'] }}; }

        /* Cover Page */
        .cover {
            text-align: center;
            padding-top: 150px;
            page-break-after: always;
        }

        .cover h1 {
            font-size: 36px;
            color: {{ $options['brand_color'] }};
            margin-bottom: 10px;
        }

        .cover .subtitle {
            font-size: 20px;
            color: #6b7280;
            margin-bottom: 60px;
        }

        .health-score {
            display: inline-block;
            width: 120px;
            height: 120px;
            line-height: 120px;
            border-radius: 60px;
            background: {{ $options['brand_color'] }};
            color: white;
            font-size: 48px;
            font-weight: bold;
        }

        .health-label {
            margin-top: 15px;
            color: #6b7280;
            font-size: 14px;
        }

        .cover-meta {
            margin-top: 80px;
            color: #9ca3af;
            font-size: 11px;
        }

        /* Table of Contents */
        .toc {
            page-break-after: always;
        }

        .toc h2 {
            color: {{ $options['brand_color'] }};
            border-bottom: 2px solid {{ $options['brand_color'] }};
            padding-bottom: 10px;
            margin-bottom: 20px;
        }

        .toc-item {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px dotted #e5e7eb;
        }

        /* Section Headers */
        h2 {
            font-size: 18px;
            color: {{ $options['brand_color'] }};
            border-bottom: 2px solid {{ $options['brand_color'] }};
            padding-bottom: 8px;
            margin: 30px 0 20px;
        }

        h3 {
            font-size: 14px;
            color: #374151;
            margin: 20px 0 10px;
        }

        /* Statistics Grid */
        .stats-grid {
            width: 100%;
            border-collapse: separate;
            border-spacing: 10px;
            margin: 20px 0;
        }

        .stat-box {
            padding: 20px;
            background: #f9fafb;
            border-radius: 8px;
            text-align: center;
        }

        .stat-value {
            font-size: 32px;
            font-weight: bold;
        }

        .stat-label {
            font-size: 11px;
            color: #6b7280;
            margin-top: 5px;
        }

        .stat-ok { color: #10b981; }
        .stat-redirect { color: #f59e0b; }
        .stat-error { color: #ef4444; }
        .stat-orphan { color: #8b5cf6; }

        /* Tables */
        table.data-table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
            font-size: 11px;
        }

        table.data-table thead tr {
            background: #f3f4f6;
        }

        table.data-table th {
            padding: 10px 8px;
            text-align: left;
            font-weight: 600;
            color: #374151;
        }

        table.data-table td {
            padding: 8px;
            border-bottom: 1px solid #e5e7eb;
        }

        table.data-table tr:hover {
            background: #f9fafb;
        }

        /* Status Indicators */
        .status-dot {
            display: inline-block;
            width: 10px;
            height: 10px;
            border-radius: 5px;
        }

        .status-ok { background: #10b981; }
        .status-redirect { background: #f59e0b; }
        .status-error { background: #ef4444; }
        .status-orphan { background: #8b5cf6; }

        /* Recommendations */
        .recommendation {
            margin-bottom: 15px;
            padding: 15px;
            border-radius: 0 8px 8px 0;
            background: #f9fafb;
        }

        .recommendation.critical { border-left: 4px solid #dc2626; }
        .recommendation.high { border-left: 4px solid #ef4444; }
        .recommendation.medium { border-left: 4px solid #f59e0b; }
        .recommendation.low { border-left: 4px solid #6b7280; }

        .recommendation-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 8px;
        }

        .recommendation-title {
            font-weight: 600;
            color: #1f2937;
        }

        .priority-badge {
            font-size: 9px;
            padding: 2px 8px;
            border-radius: 10px;
            color: white;
            text-transform: uppercase;
        }

        .priority-critical { background: #dc2626; }
        .priority-high { background: #ef4444; }
        .priority-medium { background: #f59e0b; }
        .priority-low { background: #6b7280; }

        .recommendation-description {
            color: #4b5563;
            font-size: 11px;
            margin-bottom: 5px;
        }

        .recommendation-category {
            font-size: 10px;
            color: #9ca3af;
        }

        /* Depth Distribution */
        .depth-bar {
            display: flex;
            align-items: center;
            margin: 5px 0;
        }

        .depth-label {
            width: 80px;
            font-size: 11px;
            color: #6b7280;
        }

        .depth-bar-fill {
            height: 20px;
            background: {{ $options['brand_color'] }};
            border-radius: 4px;
            min-width: 2px;
        }

        .depth-count {
            margin-left: 10px;
            font-size: 11px;
            color: #6b7280;
        }

        /* Page breaks */
        .page-break {
            page-break-after: always;
        }

        /* Footer */
        .footer {
            position: fixed;
            bottom: 20px;
            left: 40px;
            right: 40px;
            font-size: 10px;
            color: #9ca3af;
            border-top: 1px solid #e5e7eb;
            padding-top: 10px;
        }
    </style>
</head>
<body>

@if($options['include_cover'])
    <!-- Cover Page -->
    <div class="cover">
        <h1>{{ $metadata['project_name'] }}</h1>
        <p class="subtitle">Site Architecture Report</p>

        <div class="health-score">{{ $statistics['health_score'] }}</div>
        <p class="health-label">Health Score</p>

        <p class="cover-meta">
            Generated on {{ $generatedAt }}<br>
            {{ $statistics['total_pages'] }} pages &bull; {{ $statistics['total_links'] }} links
        </p>
    </div>
@endif

@if($options['include_toc'])
    <!-- Table of Contents -->
    <div class="toc">
        <h2>Table of Contents</h2>

        <div class="toc-item">
            <span>Overview Statistics</span>
            <span>2</span>
        </div>
        @if($options['include_node_list'])
            <div class="toc-item">
                <span>Page Inventory</span>
                <span>3</span>
            </div>
        @endif
        @if($options['include_recommendations'])
            <div class="toc-item">
                <span>Recommendations</span>
                <span>4</span>
            </div>
        @endif
        @if($options['include_link_analysis'])
            <div class="toc-item">
                <span>Link Analysis</span>
                <span>5</span>
            </div>
        @endif
    </div>
@endif

@if($options['include_statistics'])
    <!-- Statistics Section -->
    <h2>Overview Statistics</h2>

    <table class="stats-grid">
        <tr>
            <td class="stat-box">
                <div class="stat-value brand-color">{{ $statistics['total_pages'] }}</div>
                <div class="stat-label">Total Pages</div>
            </td>
            <td class="stat-box">
                <div class="stat-value stat-ok">{{ $statistics['ok_pages'] }}</div>
                <div class="stat-label">OK (2xx)</div>
            </td>
            <td class="stat-box">
                <div class="stat-value stat-redirect">{{ $statistics['redirect_pages'] }}</div>
                <div class="stat-label">Redirects (3xx)</div>
            </td>
            <td class="stat-box">
                <div class="stat-value stat-error">{{ $statistics['error_pages'] }}</div>
                <div class="stat-label">Errors (4xx/5xx)</div>
            </td>
        </tr>
    </table>

    <table class="stats-grid">
        <tr>
            <td class="stat-box">
                <div class="stat-value stat-orphan">{{ $statistics['orphan_pages'] }}</div>
                <div class="stat-label">Orphan Pages</div>
            </td>
            <td class="stat-box">
                <div class="stat-value brand-color">{{ $statistics['max_depth'] }}</div>
                <div class="stat-label">Max Depth</div>
            </td>
            <td class="stat-box">
                <div class="stat-value brand-color">{{ $statistics['avg_inbound_links'] }}</div>
                <div class="stat-label">Avg Inbound Links</div>
            </td>
            <td class="stat-box">
                <div class="stat-value brand-color">{{ $statistics['avg_outbound_links'] }}</div>
                <div class="stat-label">Avg Outbound Links</div>
            </td>
        </tr>
    </table>

    <h3>Depth Distribution</h3>
    @php
        $maxCount = max($statistics['depth_distribution'] ?: [1]);
    @endphp
    @foreach($statistics['depth_distribution'] as $depth => $count)
        <div class="depth-bar">
            <span class="depth-label">Depth {{ $depth }}</span>
            <div class="depth-bar-fill" style="width: {{ ($count / $maxCount) * 300 }}px;"></div>
            <span class="depth-count">{{ $count }} pages</span>
        </div>
    @endforeach

    <div class="page-break"></div>
@endif

@if($options['include_node_list'])
    <!-- Page Inventory -->
    <h2>Page Inventory</h2>

    <h3>Top Pages by Inbound Links</h3>
    <table class="data-table">
        <thead>
            <tr>
                <th>Title</th>
                <th>Path</th>
                <th style="text-align: center;">Status</th>
                <th style="text-align: center;">Depth</th>
                <th style="text-align: center;">Inbound</th>
            </tr>
        </thead>
        <tbody>
            @foreach($topPages as $node)
                <tr>
                    <td>{{ $node['title'] ?? Str::title(str_replace(['-', '_'], ' ', basename($node['path'] ?? '/'))) }}</td>
                    <td style="color: #6b7280; font-size: 10px;">{{ Str::limit($node['path'] ?? '/', 40) }}</td>
                    <td style="text-align: center;">
                        <span class="status-dot {{ ($node['http_status'] ?? 200) >= 400 ? 'status-error' : (($node['http_status'] ?? 200) >= 300 ? 'status-redirect' : 'status-ok') }}"></span>
                    </td>
                    <td style="text-align: center;">{{ $node['depth'] ?? 0 }}</td>
                    <td style="text-align: center;">{{ $node['inbound_count'] ?? 0 }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    @if(count($orphanPages) > 0)
        <h3>Orphan Pages (No Inbound Links)</h3>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Path</th>
                    <th style="text-align: center;">Depth</th>
                </tr>
            </thead>
            <tbody>
                @foreach($orphanPages as $node)
                    <tr>
                        <td>{{ $node['title'] ?? 'Untitled' }}</td>
                        <td style="color: #6b7280; font-size: 10px;">{{ $node['path'] ?? '/' }}</td>
                        <td style="text-align: center;">{{ $node['depth'] ?? 0 }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    @if(count($errorPages) > 0)
        <h3>Error Pages</h3>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Path</th>
                    <th style="text-align: center;">HTTP Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach($errorPages as $node)
                    <tr>
                        <td>{{ $node['title'] ?? 'Untitled' }}</td>
                        <td style="color: #6b7280; font-size: 10px;">{{ $node['path'] ?? '/' }}</td>
                        <td style="text-align: center; color: #ef4444;">{{ $node['http_status'] ?? 'Unknown' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <div class="page-break"></div>
@endif

@if($options['include_recommendations'] && count($recommendations) > 0)
    <!-- Recommendations -->
    <h2>Recommendations</h2>

    @foreach($recommendations as $rec)
        <div class="recommendation {{ $rec['priority'] }}">
            <div class="recommendation-header">
                <span class="recommendation-title">{{ $rec['title'] }}</span>
                <span class="priority-badge priority-{{ $rec['priority'] }}">{{ $rec['priority'] }}</span>
            </div>
            <p class="recommendation-description">{{ $rec['description'] }}</p>
            <span class="recommendation-category">{{ $rec['category'] }}</span>
        </div>
    @endforeach
@endif

</body>
</html>
