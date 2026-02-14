<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $project->name }} - Issues Report</title>
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
        .summary {
            background: #f3f4f6;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 30px;
        }
        .summary h2 {
            margin: 0 0 10px;
            font-size: 16px;
            color: #374151;
        }
        .summary-grid {
            display: table;
            width: 100%;
        }
        .summary-item {
            display: table-cell;
            text-align: center;
            padding: 10px;
        }
        .summary-item .value {
            font-size: 24px;
            font-weight: bold;
            color: #1e40af;
        }
        .summary-item .label {
            font-size: 11px;
            color: #6b7280;
            text-transform: uppercase;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            border: 1px solid #e5e7eb;
            padding: 8px;
            text-align: left;
            font-size: 10px;
        }
        th {
            background: #f9fafb;
            font-weight: 600;
            color: #374151;
        }
        tr:nth-child(even) {
            background: #f9fafb;
        }
        .category-spelling { color: #dc2626; }
        .category-grammar { color: #d97706; }
        .category-seo { color: #2563eb; }
        .category-accessibility { color: #7c3aed; }
        .severity-error {
            background: #fee2e2;
            color: #dc2626;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 9px;
        }
        .severity-warning {
            background: #fef3c7;
            color: #d97706;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 9px;
        }
        .severity-info {
            background: #dbeafe;
            color: #2563eb;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 9px;
        }
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 10px;
            color: #9ca3af;
            border-top: 1px solid #e5e7eb;
            padding-top: 15px;
        }
        .page-break {
            page-break-after: always;
        }
        .truncate {
            max-width: 150px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $project->name }}</h1>
        <p>Issues Report - Generated {{ $exportedAt->format('F j, Y \a\t g:i A') }}</p>
    </div>

    <div class="summary">
        <h2>Summary</h2>
        <div class="summary-grid">
            <div class="summary-item">
                <div class="value">{{ $stats['total'] }}</div>
                <div class="label">Total Issues</div>
            </div>
            @foreach($stats['by_severity'] as $severity => $count)
                <div class="summary-item">
                    <div class="value">{{ $count }}</div>
                    <div class="label">{{ ucfirst($severity) }}s</div>
                </div>
            @endforeach
        </div>
    </div>

    <h2>Issues by Category</h2>
    <table>
        <thead>
            <tr>
                <th>Category</th>
                <th>Count</th>
            </tr>
        </thead>
        <tbody>
            @foreach($stats['by_category'] as $category => $count)
                <tr>
                    <td class="category-{{ $category }}">{{ ucfirst($category) }}</td>
                    <td>{{ $count }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="page-break"></div>

    <h2>All Issues</h2>
    <table>
        <thead>
            <tr>
                <th style="width: 25%">Page</th>
                <th style="width: 10%">Category</th>
                <th style="width: 10%">Severity</th>
                <th style="width: 25%">Issue</th>
                <th style="width: 20%">Suggestion</th>
                <th style="width: 10%">Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach($issues as $issue)
                <tr>
                    <td class="truncate">{{ parse_url($issue->result?->scan?->url?->url ?? '', PHP_URL_PATH) ?: '/' }}</td>
                    <td class="category-{{ $issue->category }}">{{ ucfirst($issue->category) }}</td>
                    <td><span class="severity-{{ $issue->severity }}">{{ ucfirst($issue->severity) }}</span></td>
                    <td>{{ Str::limit($issue->text_excerpt ?? $issue->description ?? '', 50) }}</td>
                    <td>{{ Str::limit($issue->suggestion ?? '-', 40) }}</td>
                    <td>{{ ucfirst($issue->assignment?->status ?? 'open') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        <p>Generated by OnPageIQ - {{ config('app.url') }}</p>
    </div>
</body>
</html>
