<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Scan Report - {{ $scan->url->url }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            font-size: 12px;
            line-height: 1.5;
            color: #1f2937;
            background: #fff;
        }
        .container {
            padding: 20px;
            max-width: 100%;
        }
        .header {
            border-bottom: 2px solid #3b82f6;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
        .header h1 {
            font-size: 24px;
            color: #1f2937;
            margin-bottom: 5px;
        }
        .header .url {
            font-size: 14px;
            color: #6b7280;
            word-break: break-all;
        }
        .header .meta {
            margin-top: 10px;
            font-size: 11px;
            color: #9ca3af;
        }
        .scores {
            display: flex;
            gap: 15px;
            margin-bottom: 25px;
        }
        .score-card {
            flex: 1;
            padding: 15px;
            border-radius: 8px;
            text-align: center;
        }
        .score-card.overall { background: #f0fdf4; border: 1px solid #86efac; }
        .score-card.readability { background: #eff6ff; border: 1px solid #93c5fd; }
        .score-card.seo { background: #fef3c7; border: 1px solid #fcd34d; }
        .score-card .label {
            font-size: 11px;
            text-transform: uppercase;
            color: #6b7280;
            margin-bottom: 5px;
        }
        .score-card .value {
            font-size: 28px;
            font-weight: 700;
        }
        .score-card.overall .value { color: #16a34a; }
        .score-card.readability .value { color: #2563eb; }
        .score-card.seo .value { color: #d97706; }
        .summary {
            display: flex;
            gap: 10px;
            margin-bottom: 25px;
        }
        .summary-item {
            flex: 1;
            padding: 10px;
            border-radius: 6px;
            text-align: center;
        }
        .summary-item.errors { background: #fef2f2; border: 1px solid #fecaca; }
        .summary-item.warnings { background: #fffbeb; border: 1px solid #fde68a; }
        .summary-item.suggestions { background: #eff6ff; border: 1px solid #bfdbfe; }
        .summary-item .count {
            font-size: 20px;
            font-weight: 700;
        }
        .summary-item.errors .count { color: #dc2626; }
        .summary-item.warnings .count { color: #d97706; }
        .summary-item.suggestions .count { color: #2563eb; }
        .summary-item .label {
            font-size: 10px;
            color: #6b7280;
            text-transform: uppercase;
        }
        .section {
            margin-bottom: 25px;
        }
        .section h2 {
            font-size: 16px;
            color: #1f2937;
            margin-bottom: 10px;
            padding-bottom: 5px;
            border-bottom: 1px solid #e5e7eb;
        }
        .issue {
            padding: 12px;
            margin-bottom: 10px;
            border-radius: 6px;
            page-break-inside: avoid;
        }
        .issue.error { background: #fef2f2; border-left: 4px solid #dc2626; }
        .issue.warning { background: #fffbeb; border-left: 4px solid #d97706; }
        .issue.suggestion { background: #eff6ff; border-left: 4px solid #2563eb; }
        .issue .badges {
            display: flex;
            gap: 8px;
            margin-bottom: 8px;
        }
        .badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 10px;
            font-weight: 600;
            text-transform: uppercase;
        }
        .badge.error { background: #fecaca; color: #991b1b; }
        .badge.warning { background: #fde68a; color: #92400e; }
        .badge.suggestion { background: #bfdbfe; color: #1e40af; }
        .badge.category { background: #e5e7eb; color: #374151; }
        .issue .excerpt {
            font-family: 'Courier New', monospace;
            background: rgba(0,0,0,0.05);
            padding: 4px 8px;
            border-radius: 4px;
            margin-bottom: 6px;
        }
        .issue .suggestion-text {
            color: #16a34a;
        }
        .footer {
            margin-top: 30px;
            padding-top: 15px;
            border-top: 1px solid #e5e7eb;
            text-align: center;
            font-size: 10px;
            color: #9ca3af;
        }
        @media print {
            .container { padding: 0; }
            .issue { page-break-inside: avoid; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>OnPageIQ Scan Report</h1>
            <div class="url">{{ $scan->url->url }}</div>
            <div class="meta">
                Scanned on {{ $scan->completed_at?->format('F j, Y \a\t g:i A') }}
                · {{ ucfirst($scan->scan_type) }} scan
                · Project: {{ $scan->url->project->name }}
            </div>
        </div>

        <div class="scores">
            <div class="score-card overall">
                <div class="label">Overall Score</div>
                <div class="value">{{ number_format($scores['overall'] ?? 0) }}%</div>
            </div>
            <div class="score-card readability">
                <div class="label">Readability</div>
                <div class="value">{{ number_format($scores['readability'] ?? 0) }}%</div>
            </div>
            <div class="score-card seo">
                <div class="label">SEO</div>
                <div class="value">{{ number_format($scores['seo'] ?? 0) }}%</div>
            </div>
        </div>

        <div class="summary">
            <div class="summary-item errors">
                <div class="count">{{ $severityCounts['error'] ?? 0 }}</div>
                <div class="label">Errors</div>
            </div>
            <div class="summary-item warnings">
                <div class="count">{{ $severityCounts['warning'] ?? 0 }}</div>
                <div class="label">Warnings</div>
            </div>
            <div class="summary-item suggestions">
                <div class="count">{{ $severityCounts['suggestion'] ?? 0 }}</div>
                <div class="label">Suggestions</div>
            </div>
        </div>

        @if($issues->isNotEmpty())
            <div class="section">
                <h2>Issues Found ({{ $issues->count() }})</h2>
                @foreach($issues as $issue)
                    <div class="issue {{ $issue->severity }}">
                        <div class="badges">
                            <span class="badge {{ $issue->severity }}">{{ $issue->severity }}</span>
                            <span class="badge category">{{ $issue->category }}</span>
                        </div>
                        <div class="excerpt">{{ $issue->text_excerpt }}</div>
                        @if($issue->suggestion)
                            <div class="suggestion-text">
                                <strong>Suggestion:</strong> {{ $issue->suggestion }}
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        @else
            <div class="section">
                <h2>No Issues Found</h2>
                <p style="color: #16a34a; text-align: center; padding: 20px;">
                    Great job! Your content looks perfect.
                </p>
            </div>
        @endif

        <div class="footer">
            Generated by OnPageIQ on {{ $generatedAt->format('F j, Y \a\t g:i A') }}
        </div>
    </div>
</body>
</html>
