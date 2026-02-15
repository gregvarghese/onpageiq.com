<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Accessibility Report - {{ $project->name ?? 'Audit' }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 10pt;
            line-height: 1.4;
            color: #333;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 3px solid {{ $brandColor ?? '#2563eb' }};
        }
        .header h1 {
            font-size: 20pt;
            color: {{ $brandColor ?? '#2563eb' }};
            margin-bottom: 5px;
        }
        .header p {
            font-size: 11pt;
            color: #666;
        }
        .section {
            margin-bottom: 20px;
        }
        .section-title {
            font-size: 14pt;
            color: {{ $brandColor ?? '#2563eb' }};
            border-bottom: 1px solid #ddd;
            padding-bottom: 5px;
            margin-bottom: 10px;
        }
        .summary-box {
            background-color: #f8fafc;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 15px;
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
        .summary-number {
            font-size: 28pt;
            font-weight: bold;
        }
        .summary-label {
            font-size: 9pt;
            color: #666;
            margin-top: 5px;
        }
        .score-excellent { color: #166534; }
        .score-good { color: #3b82f6; }
        .score-fair { color: #ca8a04; }
        .score-poor { color: #dc2626; }
        .table {
            width: 100%;
            border-collapse: collapse;
            font-size: 9pt;
            margin-bottom: 15px;
        }
        .table th,
        .table td {
            padding: 8px;
            border: 1px solid #ddd;
            text-align: left;
            vertical-align: top;
        }
        .table th {
            background-color: {{ $brandColor ?? '#2563eb' }};
            color: white;
            font-weight: bold;
        }
        .table tr:nth-child(even) {
            background-color: #f9fafb;
        }
        .impact-critical {
            color: #dc2626;
            font-weight: bold;
        }
        .impact-serious {
            color: #ea580c;
            font-weight: bold;
        }
        .impact-moderate {
            color: #ca8a04;
        }
        .impact-minor {
            color: #6b7280;
        }
        .badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 3px;
            font-size: 8pt;
            font-weight: bold;
        }
        .badge-a { background: #dbeafe; color: #1e40af; }
        .badge-aa { background: #fef3c7; color: #92400e; }
        .badge-aaa { background: #f3e8ff; color: #7c3aed; }
        .footer {
            margin-top: 30px;
            padding-top: 15px;
            border-top: 1px solid #ddd;
            font-size: 8pt;
            color: #666;
            text-align: center;
        }
        .page-break {
            page-break-before: always;
        }
        .issue-card {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 5px;
            padding: 10px;
            margin-bottom: 10px;
        }
        .issue-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
        }
        .criterion-id {
            font-weight: bold;
            color: {{ $brandColor ?? '#2563eb' }};
        }
    </style>
</head>
<body>
    <div class="header">
        @if($brandLogo)
        <img src="{{ $brandLogo }}" alt="Logo" style="max-height: 50px; margin-bottom: 10px;">
        @endif
        <h1>{{ $brandName ?? 'Accessibility Report' }}</h1>
        <p>{{ $project->name ?? '' }} - {{ $url->url ?? '' }}</p>
    </div>

    <div class="section">
        <h2 class="section-title">Executive Summary</h2>
        <div class="summary-box">
            <div class="summary-grid">
                <div class="summary-item">
                    @php
                        $score = $audit->overall_score ?? 0;
                        $scoreClass = match(true) {
                            $score >= 90 => 'score-excellent',
                            $score >= 70 => 'score-good',
                            $score >= 50 => 'score-fair',
                            default => 'score-poor',
                        };
                    @endphp
                    <div class="summary-number {{ $scoreClass }}">{{ number_format($score, 0) }}%</div>
                    <div class="summary-label">Overall Score</div>
                </div>
                <div class="summary-item">
                    <div class="summary-number">{{ $summary['total'] }}</div>
                    <div class="summary-label">Total Checks</div>
                </div>
                <div class="summary-item">
                    <div class="summary-number score-excellent">{{ $summary['passed'] }}</div>
                    <div class="summary-label">Passed</div>
                </div>
                <div class="summary-item">
                    <div class="summary-number score-poor">{{ $summary['failed'] }}</div>
                    <div class="summary-label">Failed</div>
                </div>
            </div>
        </div>
        <p><strong>Audit Date:</strong> {{ $audit->completed_at?->format('F j, Y g:i A') ?? $generatedAt->format('F j, Y g:i A') }}</p>
        <p><strong>WCAG Target:</strong> {{ $audit->wcag_level_target?->value ?? 'AA' }}</p>
    </div>

    <div class="section">
        <h2 class="section-title">Issues by Impact</h2>
        <table class="table">
            <thead>
                <tr>
                    <th>Impact Level</th>
                    <th>Count</th>
                    <th>Description</th>
                </tr>
            </thead>
            <tbody>
                @foreach($byImpact as $impact)
                <tr>
                    <td class="impact-{{ $impact['impact'] }}">{{ ucfirst($impact['impact']) }}</td>
                    <td>{{ $impact['count'] }}</td>
                    <td>
                        @switch($impact['impact'])
                            @case('critical')
                                Blocks access for users with disabilities
                                @break
                            @case('serious')
                                Creates significant barriers
                                @break
                            @case('moderate')
                                Causes some difficulty
                                @break
                            @default
                                Minor inconvenience
                        @endswitch
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="section">
        <h2 class="section-title">Issues by Category</h2>
        <table class="table">
            <thead>
                <tr>
                    <th>Category</th>
                    <th>Count</th>
                </tr>
            </thead>
            <tbody>
                @foreach($byCategory as $category)
                <tr>
                    <td>{{ ucfirst($category['category']) }}</td>
                    <td>{{ $category['count'] }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="page-break"></div>

    <div class="section">
        <h2 class="section-title">Issues by WCAG Criterion</h2>
        <table class="table">
            <thead>
                <tr>
                    <th style="width: 15%;">Criterion</th>
                    <th style="width: 35%;">Name</th>
                    <th style="width: 10%;">Level</th>
                    <th style="width: 10%;">Count</th>
                    <th style="width: 30%;">Sample Issue</th>
                </tr>
            </thead>
            <tbody>
                @foreach($byCriterion as $criterion)
                <tr>
                    <td class="criterion-id">{{ $criterion['criterion_id'] }}</td>
                    <td>{{ $criterion['criterion_name'] }}</td>
                    <td>
                        <span class="badge badge-{{ strtolower($criterion['wcag_level']) }}">
                            {{ $criterion['wcag_level'] }}
                        </span>
                    </td>
                    <td>{{ $criterion['count'] }}</td>
                    <td>{{ Str::limit($criterion['issues']->first()?->message ?? '', 80) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    @if($includeDetails && $byCriterion->isNotEmpty())
    <div class="page-break"></div>

    <div class="section">
        <h2 class="section-title">Detailed Findings</h2>
        @foreach($byCriterion->take(10) as $criterion)
        <div class="issue-card">
            <div class="issue-header">
                <span class="criterion-id">{{ $criterion['criterion_id'] }} - {{ $criterion['criterion_name'] }}</span>
                <span class="badge badge-{{ strtolower($criterion['wcag_level']) }}">{{ $criterion['wcag_level'] }}</span>
            </div>
            <p><strong>{{ $criterion['count'] }} issue(s) found</strong></p>
            <ul style="margin-top: 5px; padding-left: 20px;">
                @foreach($criterion['issues']->take(3) as $issue)
                <li style="margin-bottom: 3px;">{{ Str::limit($issue->message, 100) }}</li>
                @endforeach
            </ul>
        </div>
        @endforeach
    </div>
    @endif

    <div class="footer">
        <p>Generated on {{ $generatedAt->format('F j, Y \a\t g:i A') }}</p>
        <p>{{ $brandName ?? 'OnPageIQ' }} Accessibility Report</p>
    </div>
</body>
</html>
