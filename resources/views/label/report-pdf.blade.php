<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $report['profile']['report_title'] }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; color: #161616; margin: 0; font-size: 12px; line-height: 1.55; }
        .page { padding: 32px; }
        .cover { background: linear-gradient(135deg, {{ $report['branding']['primary_color'] }}, {{ $report['branding']['secondary_color'] }}); color: #fff; padding: 36px; border-radius: 18px; }
        .muted { color: #6b7280; }
        .section { margin-top: 24px; }
        .section-title { font-size: 18px; font-weight: bold; margin-bottom: 12px; color: {{ $report['branding']['secondary_color'] }}; }
        .card { border: 1px solid #e5e7eb; border-radius: 14px; padding: 16px; margin-bottom: 14px; }
        .grid { width: 100%; }
        .grid td { vertical-align: top; padding-right: 16px; }
        .pill { display: inline-block; padding: 4px 10px; border-radius: 999px; background: #fff2ed; color: {{ $report['branding']['primary_color'] }}; font-size: 10px; font-weight: bold; text-transform: uppercase; }
        .kpi { font-size: 24px; font-weight: bold; color: {{ $report['branding']['primary_color'] }}; }
        .table { width: 100%; border-collapse: collapse; }
        .table th, .table td { border-bottom: 1px solid #e5e7eb; padding: 10px 0; text-align: left; }
        .footer { margin-top: 26px; padding-top: 14px; border-top: 1px solid #e5e7eb; font-size: 11px; color: #4b5563; }
        ul { padding-left: 18px; margin: 8px 0 0; }
    </style>
</head>
<body>
    <div class="page">
        <div class="cover">
            @if(!empty($report['branding']['logo_url']))
                <img src="{{ $report['branding']['logo_url'] }}" alt="Brand logo" style="max-width: 180px; max-height: 68px; margin-bottom: 22px;">
            @endif
            <div class="pill">Label Report</div>
            <h1 style="margin: 14px 0 8px; font-size: 30px;">{{ $report['profile']['report_title'] }}</h1>
            <p style="margin: 0 0 18px; font-size: 14px;">{{ $report['profile']['client_name'] }} | {{ $report['profile']['client_website'] }}</p>
            <p style="margin: 0;">Reporting Period: {{ $report['profile']['reporting_period_label'] }}</p>
        </div>

        @if($report['sections']['executive_summary']['available'])
            <div class="section">
                <div class="section-title">Executive Summary</div>
                <div class="card">
                    @if(!empty($report['sections']['executive_summary']['custom_summary']))
                        <p style="margin-top: 0;">{{ $report['sections']['executive_summary']['custom_summary'] }}</p>
                    @endif
                    @if(!empty($report['sections']['executive_summary']['summary_bullets']))
                        <ul>
                            @foreach($report['sections']['executive_summary']['summary_bullets'] as $bullet)
                                <li>{{ $bullet }}</li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>
        @endif

        <table class="grid section">
            <tr>
                @if($report['sections']['keyword_overview']['available'])
                    <td style="width: 50%;">
                        <div class="section-title">Keyword Overview</div>
                        <div class="card">
                            @if(!empty($report['sections']['keyword_overview']['tracked_keywords']))
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Keyword</th>
                                            <th>Position</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($report['sections']['keyword_overview']['tracked_keywords'] as $keyword)
                                            <tr>
                                                <td>{{ $keyword['keyword'] }}</td>
                                                <td>{{ $keyword['position'] ?? 'Not ranked yet' }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            @elseif(!empty($report['sections']['keyword_overview']['target_keywords']))
                                <ul>
                                    @foreach($report['sections']['keyword_overview']['target_keywords'] as $keyword)
                                        <li>{{ $keyword }}</li>
                                    @endforeach
                                </ul>
                            @else
                                <p class="muted" style="margin: 0;">No keyword data is available yet for this report profile.</p>
                            @endif
                        </div>
                    </td>
                @endif

                @if($report['sections']['backlink_overview']['available'])
                    <td style="width: 50%;">
                        <div class="section-title">Backlink Overview</div>
                        <div class="card">
                            <div class="kpi">{{ $report['sections']['backlink_overview']['total_backlinks'] ?? 'N/A' }}</div>
                            <div class="muted">Total backlinks</div>
                            <p style="margin-top: 14px;"><strong>Referring domains:</strong> {{ $report['sections']['backlink_overview']['referring_domains'] ?? 'N/A' }}</p>
                            @if(!empty($report['sections']['backlink_overview']['top_ref_domains']))
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Referring Domain</th>
                                            <th>Links</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($report['sections']['backlink_overview']['top_ref_domains'] as $domain)
                                            <tr>
                                                <td>{{ $domain['domain'] }}</td>
                                                <td>{{ $domain['backlinks_count'] }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            @endif
                        </div>
                    </td>
                @endif
            </tr>
        </table>

        @if($report['sections']['technical_seo_summary']['available'])
            <div class="section">
                <div class="section-title">Technical SEO Summary</div>
                <div class="card">
                    <table class="grid">
                        <tr>
                            <td style="width: 33%;">
                                <div class="kpi">{{ $report['sections']['technical_seo_summary']['health_score'] ?? 'N/A' }}</div>
                                <div class="muted">Health score</div>
                            </td>
                            <td style="width: 33%;">
                                <div class="kpi">{{ $report['sections']['technical_seo_summary']['pages_crawled'] ?? 'N/A' }}</div>
                                <div class="muted">Pages crawled</div>
                            </td>
                            <td style="width: 33%;">
                                <div class="kpi">{{ $report['sections']['technical_seo_summary']['issue_counts']['critical'] ?? 0 }}</div>
                                <div class="muted">Critical issues</div>
                            </td>
                        </tr>
                    </table>
                    @if(!empty($report['sections']['technical_seo_summary']['top_issues']))
                        <table class="table" style="margin-top: 14px;">
                            <thead>
                                <tr>
                                    <th>Severity</th>
                                    <th>Issue</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($report['sections']['technical_seo_summary']['top_issues'] as $issue)
                                    <tr>
                                        <td>{{ ucfirst($issue['severity']) }}</td>
                                        <td>{{ $issue['message'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                </div>
            </div>
        @endif

        @if($report['sections']['recommendations']['available'])
            <div class="section">
                <div class="section-title">Recommendations</div>
                <div class="card">
                    <ul>
                        @foreach($report['sections']['recommendations']['items'] as $item)
                            <li>{{ $item }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @endif

        <div class="footer">
            <strong>{{ $report['branding']['brand_name'] }}</strong><br>
            {{ $report['sections']['footer_branding']['footer_text'] }}<br>
            @if($report['sections']['footer_branding']['website']){{ $report['sections']['footer_branding']['website'] }}<br>@endif
            @if($report['sections']['footer_branding']['support_email']){{ $report['sections']['footer_branding']['support_email'] }}<br>@endif
            @if($report['sections']['footer_branding']['support_phone']){{ $report['sections']['footer_branding']['support_phone'] }}<br>@endif
            @if($report['sections']['footer_branding']['company_address']){{ $report['sections']['footer_branding']['company_address'] }}@endif
        </div>
    </div>
</body>
</html>
