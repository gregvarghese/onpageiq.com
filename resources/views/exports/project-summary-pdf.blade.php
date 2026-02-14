<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $project->name }} - Summary Report</title>
    <style>
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 12px;
            line-height: 1.4;
            color: #333;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #3b82f6;
            padding-bottom: 20px;
        }
        .header h1 {
            margin: 0;
            color: #1e40af;
            font-size: 24px;
        }
        .header p {
            margin: 5px 0 0;
            color: #6b7280;
        }
        .score-card {
            text-align: center;
            background: linear-gradient(135deg, #3b82f6, #1e40af);
            color: white;
            padding: 30px;
            border-radius: 12px;
            margin-bottom: 30px;
        }
        .score-card .score {
            font-size: 72px;
            font-weight: bold;
        }
        .score-card .label {
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 2px;
        }
        .stats-grid {
            display: table;
            width: 100%;
            margin-bottom: 30px;
        }
        .stat-card {
            display: table-cell;
            text-align: center;
            padding: 20px;
            background: #f9fafb;
            border: 1px solid #e5e7eb;
        }
        .stat-card:first-child {
            border-radius: 8px 0 0 8px;
        }
        .stat-card:last-child {
            border-radius: 0 8px 8px 0;
        }
        .stat-card .value {
            font-size: 32px;
            font-weight: bold;
            color: #1e40af;
        }
        .stat-card .label {
            font-size: 11px;
            color: #6b7280;
            text-transform: uppercase;
            margin-top: 5px;
        }
        .stat-card.error .value { color: #dc2626; }
        .stat-card.warning .value { color: #d97706; }
        .stat-card.success .value { color: #16a34a; }
        h2 {
            color: #374151;
            font-size: 16px;
            margin-top: 30px;
            margin-bottom: 15px;
            border-bottom: 1px solid #e5e7eb;
            padding-bottom: 10px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        th, td {
            border: 1px solid #e5e7eb;
            padding: 10px;
            text-align: left;
            font-size: 11px;
        }
        th {
            background: #f9fafb;
            font-weight: 600;
            color: #374151;
        }
        tr:nth-child(even) {
            background: #f9fafb;
        }
        .status-completed {
            color: #16a34a;
        }
        .status-failed {
            color: #dc2626;
        }
        .status-pending {
            color: #d97706;
        }
        .issue-count {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 10px;
            font-weight: 600;
        }
        .issue-count.has-issues {
            background: #fee2e2;
            color: #dc2626;
        }
        .issue-count.no-issues {
            background: #dcfce7;
            color: #16a34a;
        }
        .category-breakdown {
            margin-top: 20px;
        }
        .category-bar {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
        }
        .category-label {
            width: 120px;
            font-weight: 500;
        }
        .category-bar-fill {
            height: 20px;
            border-radius: 4px;
            min-width: 30px;
            text-align: center;
            color: white;
            font-size: 10px;
            line-height: 20px;
        }
        .category-spelling { background: #dc2626; }
        .category-grammar { background: #d97706; }
        .category-seo { background: #2563eb; }
        .category-accessibility { background: #7c3aed; }
        .category-links { background: #ea580c; }
        .category-readability { background: #16a34a; }
        .footer {
            margin-top: 40px;
            text-align: center;
            font-size: 10px;
            color: #9ca3af;
            border-top: 1px solid #e5e7eb;
            padding-top: 15px;
        }
        .page-break {
            page-break-after: always;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $project->name }}</h1>
        <p>Project Summary Report - Generated {{ $exportedAt->format('F j, Y \a\t g:i A') }}</p>
    </div>

    @php
        $score = $stats['total_urls'] > 0
            ? max(0, 100 - (($stats['errors'] * 5 + $stats['warnings'] * 2) / max(1, $stats['total_urls'])))
            : 100;
        $score = round($score);
    @endphp

    <div class="score-card">
        <div class="score">{{ $score }}%</div>
        <div class="label">Overall Quality Score</div>
    </div>

    <div class="stats-grid">
        <div class="stat-card">
            <div class="value">{{ $stats['total_urls'] }}</div>
            <div class="label">Total Pages</div>
        </div>
        <div class="stat-card success">
            <div class="value">{{ $stats['scanned_urls'] }}</div>
            <div class="label">Scanned</div>
        </div>
        <div class="stat-card error">
            <div class="value">{{ $stats['errors'] }}</div>
            <div class="label">Errors</div>
        </div>
        <div class="stat-card warning">
            <div class="value">{{ $stats['warnings'] }}</div>
            <div class="label">Warnings</div>
        </div>
    </div>

    <h2>Issues by Category</h2>
    @if(!empty($stats['by_category']))
        <table>
            <thead>
                <tr>
                    <th>Category</th>
                    <th>Count</th>
                    <th>Percentage</th>
                </tr>
            </thead>
            <tbody>
                @foreach($stats['by_category'] as $category => $count)
                    <tr>
                        <td>{{ ucfirst($category) }}</td>
                        <td>{{ $count }}</td>
                        <td>{{ $stats['total_issues'] > 0 ? round(($count / $stats['total_issues']) * 100, 1) : 0 }}%</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <p style="color: #16a34a; text-align: center; padding: 20px;">No issues found - excellent!</p>
    @endif

    <div class="page-break"></div>

    <h2>Page Details</h2>
    <table>
        <thead>
            <tr>
                <th style="width: 50%">URL</th>
                <th style="width: 15%">Status</th>
                <th style="width: 15%">Last Scanned</th>
                <th style="width: 20%">Issues</th>
            </tr>
        </thead>
        <tbody>
            @foreach($urls as $url)
                <tr>
                    <td>{{ Str::limit($url->url, 60) }}</td>
                    <td class="status-{{ $url->status }}">{{ ucfirst($url->status) }}</td>
                    <td>{{ $url->last_scanned_at?->format('M j, Y') ?? 'Never' }}</td>
                    <td>
                        @php $issueCount = $url->latestScan?->result?->issues?->count() ?? 0; @endphp
                        <span class="issue-count {{ $issueCount > 0 ? 'has-issues' : 'no-issues' }}">
                            {{ $issueCount }} {{ Str::plural('issue', $issueCount) }}
                        </span>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        <p>Generated by OnPageIQ - {{ config('app.url') }}</p>
        <p>This report contains {{ $stats['total_urls'] }} pages with {{ $stats['total_issues'] }} total issues.</p>
    </div>
</body>
</html>
