<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VPAT {{ $vpat->vpat_version }} - {{ $productInfo['name'] }}</title>
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
            border-bottom: 2px solid #2563eb;
        }
        .header h1 {
            font-size: 18pt;
            color: #1e40af;
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
            color: #1e40af;
            border-bottom: 1px solid #ddd;
            padding-bottom: 5px;
            margin-bottom: 10px;
        }
        .info-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        .info-table th,
        .info-table td {
            padding: 8px;
            text-align: left;
            border: 1px solid #ddd;
        }
        .info-table th {
            background-color: #f3f4f6;
            width: 30%;
            font-weight: bold;
        }
        .criteria-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 9pt;
        }
        .criteria-table th,
        .criteria-table td {
            padding: 6px;
            border: 1px solid #ddd;
            vertical-align: top;
        }
        .criteria-table th {
            background-color: #1e40af;
            color: white;
            text-align: left;
        }
        .criteria-table tr:nth-child(even) {
            background-color: #f9fafb;
        }
        .principle-header {
            background-color: #e5e7eb !important;
            font-weight: bold;
        }
        .principle-header td {
            font-size: 11pt;
            color: #1f2937;
        }
        .conformance-supports {
            color: #166534;
            font-weight: bold;
        }
        .conformance-partial {
            color: #ca8a04;
            font-weight: bold;
        }
        .conformance-does-not {
            color: #dc2626;
            font-weight: bold;
        }
        .conformance-na {
            color: #6b7280;
        }
        .conformance-not-evaluated {
            color: #9ca3af;
            font-style: italic;
        }
        .level-badge {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 8pt;
            font-weight: bold;
        }
        .level-a {
            background-color: #dbeafe;
            color: #1e40af;
        }
        .level-aa {
            background-color: #fef3c7;
            color: #92400e;
        }
        .summary-box {
            background-color: #f3f4f6;
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
            font-size: 24pt;
            font-weight: bold;
            color: #1e40af;
        }
        .summary-label {
            font-size: 9pt;
            color: #666;
        }
        .footer {
            margin-top: 30px;
            padding-top: 15px;
            border-top: 1px solid #ddd;
            font-size: 8pt;
            color: #666;
        }
        .disclaimer {
            background-color: #fef3c7;
            padding: 10px;
            border-radius: 5px;
            margin-top: 15px;
            font-size: 8pt;
        }
        .page-break {
            page-break-before: always;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Voluntary Product Accessibility Template (VPAT)</h1>
        <p>Version {{ $vpat->vpat_version }} - WCAG 2.1 Report</p>
    </div>

    <div class="section">
        <h2 class="section-title">Product Information</h2>
        <table class="info-table">
            <tr>
                <th>Product Name</th>
                <td>{{ $productInfo['name'] }}</td>
            </tr>
            @if($productInfo['version'])
            <tr>
                <th>Product Version</th>
                <td>{{ $productInfo['version'] }}</td>
            </tr>
            @endif
            @if($productInfo['description'])
            <tr>
                <th>Product Description</th>
                <td>{{ $productInfo['description'] }}</td>
            </tr>
            @endif
            @if($productInfo['vendor'])
            <tr>
                <th>Vendor</th>
                <td>{{ $productInfo['vendor'] }}</td>
            </tr>
            @endif
            @if($productInfo['contact'])
            <tr>
                <th>Contact</th>
                <td>{{ $productInfo['contact'] }}</td>
            </tr>
            @endif
            <tr>
                <th>Evaluation Date</th>
                <td>{{ $productInfo['evaluationDate'] }}</td>
            </tr>
            @if($productInfo['evaluationMethods'])
            <tr>
                <th>Evaluation Methods</th>
                <td>{{ $productInfo['evaluationMethods'] }}</td>
            </tr>
            @endif
        </table>
    </div>

    <div class="section">
        <h2 class="section-title">Conformance Summary</h2>
        <div class="summary-box">
            <div class="summary-grid">
                <div class="summary-item">
                    <div class="summary-number conformance-supports">{{ $summary['byConformance']['supports'] ?? 0 }}</div>
                    <div class="summary-label">Supports</div>
                </div>
                <div class="summary-item">
                    <div class="summary-number conformance-partial">{{ $summary['byConformance']['partially_supports'] ?? 0 }}</div>
                    <div class="summary-label">Partially Supports</div>
                </div>
                <div class="summary-item">
                    <div class="summary-number conformance-does-not">{{ $summary['byConformance']['does_not_support'] ?? 0 }}</div>
                    <div class="summary-label">Does Not Support</div>
                </div>
                <div class="summary-item">
                    <div class="summary-number conformance-na">{{ $summary['byConformance']['not_applicable'] ?? 0 }}</div>
                    <div class="summary-label">Not Applicable</div>
                </div>
            </div>
        </div>
        <p><strong>Completion:</strong> {{ number_format($summary['completionPercentage'], 0) }}% of criteria evaluated</p>
    </div>

    <div class="page-break"></div>

    <div class="section">
        <h2 class="section-title">WCAG 2.1 Report</h2>
        <p style="margin-bottom: 10px; font-size: 9pt;">
            <strong>Note:</strong> When a criterion is marked "Supports", the product functionality meets the criterion.
            "Partially Supports" means some functionality meets the criterion. "Does Not Support" means the majority
            of functionality does not meet the criterion. "Not Applicable" means the criterion is not relevant.
        </p>

        <table class="criteria-table">
            <thead>
                <tr>
                    <th style="width: 10%;">Criterion</th>
                    <th style="width: 25%;">Name</th>
                    <th style="width: 8%;">Level</th>
                    <th style="width: 17%;">Conformance</th>
                    <th style="width: 40%;">Remarks</th>
                </tr>
            </thead>
            <tbody>
                @foreach($wcagReport as $principle => $criteria)
                    <tr class="principle-header">
                        <td colspan="5">{{ $principle }}</td>
                    </tr>
                    @foreach($criteria as $criterion)
                        <tr>
                            <td>{{ $criterion['id'] }}</td>
                            <td>{{ $criterion['name'] }}</td>
                            <td>
                                <span class="level-badge level-{{ strtolower($criterion['wcagLevel']) }}">
                                    {{ $criterion['wcagLevel'] }}
                                </span>
                            </td>
                            <td>
                                @php
                                    $conformanceClass = match($criterion['conformanceLevel']->value) {
                                        'supports' => 'conformance-supports',
                                        'partially_supports' => 'conformance-partial',
                                        'does_not_support' => 'conformance-does-not',
                                        'not_applicable' => 'conformance-na',
                                        default => 'conformance-not-evaluated',
                                    };
                                @endphp
                                <span class="{{ $conformanceClass }}">
                                    {{ $criterion['conformanceLabel'] }}
                                </span>
                            </td>
                            <td>{{ $criterion['remarks'] }}</td>
                        </tr>
                    @endforeach
                @endforeach
            </tbody>
        </table>
    </div>

    @if($vpat->legal_disclaimer)
    <div class="disclaimer">
        <strong>Legal Disclaimer:</strong> {{ $vpat->legal_disclaimer }}
    </div>
    @endif

    <div class="footer">
        <p>Generated on {{ $generatedAt->format('F j, Y \a\t g:i A') }}</p>
        <p>This VPAT was created using OnPageIQ Accessibility Audit System.</p>
    </div>
</body>
</html>
