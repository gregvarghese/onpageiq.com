<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Scan Comparison Report</title>
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
        .summary {
            display: flex;
            gap: 15px;
            margin-bottom: 25px;
        }
        .summary-card {
            flex: 1;
            padding: 15px;
            border-radius: 8px;
            text-align: center;
        }
        .summary-card.fixed { background: #f0fdf4; border: 1px solid #86efac; }
        .summary-card.new { background: #fef2f2; border: 1px solid #fecaca; }
        .summary-card.unchanged { background: #f3f4f6; border: 1px solid #d1d5db; }
        .summary-card.score { background: #eff6ff; border: 1px solid #93c5fd; }
        .summary-card .label {
            font-size: 11px;
            text-transform: uppercase;
            color: #6b7280;
            margin-bottom: 5px;
        }
        .summary-card .value {
            font-size: 28px;
            font-weight: 700;
        }
        .summary-card.fixed .value { color: #16a34a; }
        .summary-card.new .value { color: #dc2626; }
        .summary-card.unchanged .value { color: #6b7280; }
        .summary-card.score .value { color: #2563eb; }
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
        .score-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .score-table th,
        .score-table td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #e5e7eb;
        }
        .score-table th {
            background: #f9fafb;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 10px;
            color: #6b7280;
        }
        .change-positive { color: #16a34a; }
        .change-negative { color: #dc2626; }
        .change-neutral { color: #6b7280; }
        .issue {
            padding: 12px;
            margin-bottom: 10px;
            border-radius: 6px;
            page-break-inside: avoid;
        }
        .issue.fixed { background: #f0fdf4; border-left: 4px solid #16a34a; }
        .issue.new { background: #fef2f2; border-left: 4px solid #dc2626; }
        .issue.unchanged { background: #f3f4f6; border-left: 4px solid #9ca3af; }
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
        .badge.fixed { background: #bbf7d0; color: #166534; }
        .badge.new { background: #fecaca; color: #991b1b; }
        .badge.unchanged { background: #e5e7eb; color: #374151; }
        .badge.category { background: #e5e7eb; color: #374151; }
        .issue .excerpt {
            font-family: 'Courier New', monospace;
            background: rgba(0,0,0,0.05);
            padding: 4px 8px;
            border-radius: 4px;
        }
        .issue.fixed .excerpt {
            text-decoration: line-through;
            opacity: 0.7;
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
            <h1>Scan Comparison Report</h1>
            <div class="url">{{ $comparison->current->scan->url->url }}</div>
            <div class="meta">
                Comparing: {{ $comparison->baseline->created_at->format('M j, Y g:i A') }}
                → {{ $comparison->current->created_at->format('M j, Y g:i A') }}
            </div>
        </div>

        <div class="summary">
            <div class="summary-card fixed">
                <div class="label">Fixed</div>
                <div class="value">{{ $comparison->fixedCount() }}</div>
            </div>
            <div class="summary-card new">
                <div class="label">New Issues</div>
                <div class="value">{{ $comparison->newCount() }}</div>
            </div>
            <div class="summary-card unchanged">
                <div class="label">Unchanged</div>
                <div class="value">{{ $comparison->unchangedCount() }}</div>
            </div>
            <div class="summary-card score">
                <div class="label">Score Change</div>
                <div class="value">{{ $comparison->overallScoreChange() >= 0 ? '+' : '' }}{{ number_format($comparison->overallScoreChange(), 1) }}%</div>
            </div>
        </div>

        <div class="section">
            <h2>Score Changes</h2>
            <table class="score-table">
                <thead>
                    <tr>
                        <th>Metric</th>
                        <th>Baseline</th>
                        <th>Current</th>
                        <th>Change</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($comparison->scoreChanges as $metric => $data)
                        <tr>
                            <td>{{ ucfirst($metric) }}</td>
                            <td>{{ number_format($data['baseline'], 1) }}%</td>
                            <td>{{ number_format($data['current'], 1) }}%</td>
                            <td class="{{ $data['improved'] ? 'change-positive' : ($data['change'] < 0 ? 'change-negative' : 'change-neutral') }}">
                                {{ $data['change'] >= 0 ? '+' : '' }}{{ number_format($data['change'], 1) }}%
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if($comparison->fixedIssues->isNotEmpty())
            <div class="section">
                <h2>Fixed Issues ({{ $comparison->fixedCount() }})</h2>
                @foreach($comparison->fixedIssues as $issue)
                    <div class="issue fixed">
                        <div class="badges">
                            <span class="badge fixed">Fixed</span>
                            <span class="badge category">{{ $issue->category }}</span>
                        </div>
                        <div class="excerpt">{{ $issue->text_excerpt }}</div>
                    </div>
                @endforeach
            </div>
        @endif

        @if($comparison->newIssues->isNotEmpty())
            <div class="section">
                <h2>New Issues ({{ $comparison->newCount() }})</h2>
                @foreach($comparison->newIssues as $issue)
                    <div class="issue new">
                        <div class="badges">
                            <span class="badge new">New</span>
                            <span class="badge category">{{ $issue->category }}</span>
                        </div>
                        <div class="excerpt">{{ $issue->text_excerpt }}</div>
                        @if($issue->suggestion)
                            <div style="color: #16a34a; margin-top: 6px;">
                                <strong>Suggestion:</strong> {{ $issue->suggestion }}
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif

        @if($comparison->unchangedIssues->isNotEmpty())
            <div class="section">
                <h2>Unchanged Issues ({{ $comparison->unchangedCount() }})</h2>
                @foreach($comparison->unchangedIssues as $pair)
                    <div class="issue unchanged">
                        <div class="badges">
                            <span class="badge unchanged">Unchanged</span>
                            <span class="badge category">{{ $pair['current']->category }}</span>
                        </div>
                        <div class="excerpt">{{ $pair['current']->text_excerpt }}</div>
                    </div>
                @endforeach
            </div>
        @endif

        <div class="footer">
            Generated by OnPageIQ on {{ $generatedAt->format('F j, Y \a\t g:i A') }}
        </div>
    </div>
</body>
</html>
