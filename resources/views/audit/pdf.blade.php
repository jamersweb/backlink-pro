<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SEO Audit Report - {{ $audit->url }}</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        h1 {
            color: #1a202c;
            border-bottom: 3px solid #4299e1;
            padding-bottom: 10px;
        }
        h2 {
            color: #2d3748;
            margin-top: 30px;
            border-bottom: 2px solid #e2e8f0;
            padding-bottom: 5px;
        }
        .score {
            font-size: 48px;
            font-weight: bold;
            color: #4299e1;
        }
        .grade {
            font-size: 24px;
            font-weight: bold;
            color: #4299e1;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
        }
        th {
            background-color: #f7fafc;
            font-weight: bold;
        }
        .badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
        }
        .badge-high {
            background-color: #fed7d7;
            color: #742a2a;
        }
        .badge-medium {
            background-color: #feebc8;
            color: #7c2d12;
        }
        .badge-low {
            background-color: #bee3f8;
            color: #2c5282;
        }
        .code-block {
            background-color: #1a202c;
            color: #68d391;
            padding: 15px;
            border-radius: 5px;
            overflow-x: auto;
            font-family: 'Courier New', monospace;
            font-size: 12px;
        }
        .metrics-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            margin: 20px 0;
        }
        .metric-box {
            background-color: #f7fafc;
            padding: 15px;
            border-radius: 5px;
        }
        @media print {
            body {
                padding: 0;
            }
        }
    </style>
</head>
<body>
    <h1>SEO Audit Report</h1>
    
    <div style="margin-bottom: 30px;">
        <p><strong>URL:</strong> {{ $audit->url }}</p>
        <p><strong>Date:</strong> {{ $audit->created_at->format('F j, Y g:i A') }}</p>
    </div>

    @if($audit->status === 'completed')
        <h2>Overview</h2>
        <table>
            <tbody>
                <tr><th>Overall Score</th><td>{{ $audit->overall_score ?? 'N/A' }}</td></tr>
                <tr><th>Overall Grade</th><td>{{ $audit->overall_grade ?? 'N/A' }}</td></tr>
                <tr><th>Pages Crawled</th><td>{{ $audit->pages_scanned ?? 'N/A' }}</td></tr>
            </tbody>
        </table>

        <h2>Category Scores</h2>
        <table>
            <tbody>
                <tr><th>Onpage</th><td>{{ data_get($audit->category_scores, 'onpage', 'N/A') }}</td></tr>
                <tr><th>Technical</th><td>{{ data_get($audit->category_scores, 'technical', 'N/A') }}</td></tr>
                <tr><th>Performance</th><td>{{ data_get($audit->category_scores, 'performance', 'N/A') }}</td></tr>
                <tr><th>Links</th><td>{{ data_get($audit->category_scores, 'links', 'N/A') }}</td></tr>
                <tr><th>Social</th><td>{{ data_get($audit->category_scores, 'social', 'N/A') }}</td></tr>
                <tr><th>Usability</th><td>{{ data_get($audit->category_scores, 'usability', 'N/A') }}</td></tr>
                <tr><th>Local</th><td>{{ data_get($audit->category_scores, 'local', 'N/A') }}</td></tr>
                <tr><th>Security</th><td>{{ data_get($audit->category_scores, 'security', 'N/A') }}</td></tr>
            </tbody>
        </table>

        <h2>Crawl Statistics</h2>
        <table>
            <tbody>
                <tr><th>Broken Links</th><td>{{ data_get($audit->crawl_stats, 'broken_links_count', 'N/A') }}</td></tr>
                <tr><th>Redirect Chains</th><td>{{ data_get($audit->crawl_stats, 'redirect_chain_count', 'N/A') }}</td></tr>
                <tr><th>Duplicate Titles</th><td>{{ data_get($audit->crawl_stats, 'duplicate_titles_groups', 'N/A') }}</td></tr>
                <tr><th>Duplicate Meta</th><td>{{ data_get($audit->crawl_stats, 'duplicate_meta_groups', 'N/A') }}</td></tr>
            </tbody>
        </table>

        <h2>Homepage Metrics</h2>
        <table>
            <tbody>
                <tr><th>Title</th><td>{{ $page->title ?? 'N/A' }}</td></tr>
                <tr><th>Title Length</th><td>{{ $page->title_len ?? 'N/A' }}</td></tr>
                <tr><th>Meta Description</th><td>{{ $page->meta_description ?? 'N/A' }}</td></tr>
                <tr><th>Meta Length</th><td>{{ $page->meta_len ?? 'N/A' }}</td></tr>
                <tr><th>H1 Count</th><td>{{ $page->h1_count ?? 'N/A' }}</td></tr>
                <tr><th>H2 Count</th><td>{{ $page->h2_count ?? 'N/A' }}</td></tr>
                <tr><th>H3 Count</th><td>{{ $page->h3_count ?? 'N/A' }}</td></tr>
                <tr><th>Word Count</th><td>{{ $page->word_count ?? 'N/A' }}</td></tr>
                <tr><th>Images Total</th><td>{{ $page->images_total ?? 'N/A' }}</td></tr>
                <tr><th>Images Missing Alt</th><td>{{ $page->images_missing_alt ?? 'N/A' }}</td></tr>
                <tr><th>Internal Links</th><td>{{ $page->internal_links_count ?? 'N/A' }}</td></tr>
                <tr><th>External Links</th><td>{{ $page->external_links_count ?? 'N/A' }}</td></tr>
            </tbody>
        </table>

        @if($issues->count() > 0)
            <h2>Issues</h2>
            <table>
                <thead>
                    <tr>
                        <th>Issue</th>
                        <th>Impact</th>
                        <th>Affected</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($issues as $issue)
                        <tr>
                            <td>{{ $issue->title }}</td>
                            <td>{{ ucfirst($issue->impact) }}</td>
                            <td>{{ $issue->affected_count }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    @elseif($audit->status === 'failed')
        <div style="background-color: #fed7d7; color: #742a2a; padding: 20px; border-radius: 5px;">
            <h2>Audit Failed</h2>
            <p>{{ $audit->error ?: 'An error occurred while running the audit.' }}</p>
        </div>
    @endif

    <footer style="margin-top: 50px; padding-top: 20px; border-top: 1px solid #e2e8f0; text-align: center; color: #718096; font-size: 12px;">
        <p>Generated by BacklinkPro SEO Audit Tool</p>
        <p>{{ now()->format('F j, Y g:i A') }}</p>
    </footer>
</body>
</html>
