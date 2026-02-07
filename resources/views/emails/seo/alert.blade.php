<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SEO Alert: {{ $alert->title }}</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            background-color: #ffffff;
            border-radius: 8px;
            padding: 30px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .header {
            padding-bottom: 20px;
            margin-bottom: 30px;
            border-bottom: 3px solid {{ $severityColor }};
        }
        .severity-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            background-color: {{ $severityColor }};
            color: #ffffff;
            margin-bottom: 15px;
        }
        .header h1 {
            color: #1f2937;
            margin: 0;
            font-size: 24px;
        }
        .alert-message {
            background-color: #f9fafb;
            border-left: 4px solid {{ $severityColor }};
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .details-section {
            margin: 25px 0;
        }
        .details-section h3 {
            font-size: 16px;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 10px;
        }
        .details-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        .details-table td {
            padding: 8px 0;
            border-bottom: 1px solid #e5e7eb;
        }
        .details-table td:first-child {
            color: #6b7280;
            font-weight: 500;
            width: 40%;
        }
        .details-table td:last-child {
            color: #1f2937;
            font-weight: 600;
        }
        .affected-items {
            background-color: #f9fafb;
            padding: 15px;
            border-radius: 6px;
            margin-top: 15px;
        }
        .affected-items ul {
            margin: 10px 0 0 0;
            padding-left: 20px;
        }
        .affected-items li {
            margin: 5px 0;
            color: #4b5563;
        }
        .cta-button {
            display: inline-block;
            background-color: #3b82f6;
            color: #ffffff;
            padding: 12px 24px;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
            margin-top: 20px;
            text-align: center;
        }
        .cta-button:hover {
            background-color: #2563eb;
        }
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
            text-align: center;
            color: #6b7280;
            font-size: 14px;
        }
        .change-indicator {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 14px;
            font-weight: 600;
            margin-left: 8px;
        }
        .change-indicator.positive {
            background-color: #d1fae5;
            color: #065f46;
        }
        .change-indicator.negative {
            background-color: #fee2e2;
            color: #991b1b;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <span class="severity-badge">{{ ucfirst($alert->severity) }}</span>
            <h1>{{ $alert->title }}</h1>
        </div>

        <div class="alert-message">
            <p style="margin: 0; font-size: 16px;">{{ $alert->message }}</p>
        </div>

        @if($alert->diff)
        <div class="details-section">
            <h3>Details</h3>
            <table class="details-table">
                @if(isset($alert->diff['baseline_avg']))
                <tr>
                    <td>Baseline Average:</td>
                    <td>{{ number_format($alert->diff['baseline_avg']) }}</td>
                </tr>
                @endif
                @if(isset($alert->diff['yesterday']))
                <tr>
                    <td>Current Value:</td>
                    <td>{{ number_format($alert->diff['yesterday']) }}</td>
                </tr>
                @endif
                @if(isset($alert->diff['drop_percent']))
                <tr>
                    <td>Change:</td>
                    <td>
                        <span class="change-indicator negative">-{{ number_format($alert->diff['drop_percent'], 1) }}%</span>
                    </td>
                </tr>
                @endif
                @if(isset($alert->diff['affected_count']))
                <tr>
                    <td>Affected Items:</td>
                    <td>{{ $alert->diff['affected_count'] }}</td>
                </tr>
                @endif
            </table>
        </div>
        @endif

        @if(isset($alert->diff['top_affected']) && is_array($alert->diff['top_affected']) && count($alert->diff['top_affected']) > 0)
        <div class="details-section">
            <h3>Top Affected Keywords/Pages</h3>
            <div class="affected-items">
                <ul>
                    @foreach(array_slice($alert->diff['top_affected'], 0, 5) as $item)
                    <li>
                        <strong>{{ $item['keyword'] ?? $item['page'] ?? 'N/A' }}</strong>
                        @if(isset($item['baseline_avg']) && isset($item['current']))
                            <span style="color: #6b7280;">({{ number_format($item['baseline_avg'], 1) }} → {{ number_format($item['current'], 1) }})</span>
                        @endif
                    </li>
                    @endforeach
                </ul>
            </div>
        </div>
        @endif

        <div style="text-align: center; margin: 30px 0;">
            <a href="{{ route('seo.dashboard', ['organization' => $organization->id]) }}" class="cta-button">View Dashboard</a>
        </div>

        <div class="footer">
            <p>This is an automated alert from BacklinkPro.</p>
            <p>To manage your alert preferences, visit your <a href="{{ route('seo.alerts.index', ['organization' => $organization->id]) }}" style="color: #3b82f6;">SEO Alerts settings</a>.</p>
        </div>
    </div>
</body>
</html>
