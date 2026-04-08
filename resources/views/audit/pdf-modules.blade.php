{{-- Renders report_modules payloads like AuditReportView ExtendedModulesInTab (no interactive filters — full rows, PDF-safe limits). --}}
@php
    $modRowCap = (int) ($moduleRowCap ?? 120);
    $tableRowCap = (int) ($tableRowCap ?? 80);
    $fmt = $fmtVal ?? function ($v) {
        if ($v === null || $v === '') {
            return '—';
        }
        return is_numeric($v) ? (strpos((string) $v, '.') !== false ? number_format((float) $v, 2) : (string) $v) : $v;
    };
    $strTrunc = $strTrunc ?? fn ($s, $n = 180) => \Illuminate\Support\Str::limit(is_scalar($s) ? (string) $s : json_encode($s), $n);
@endphp

@foreach($modules ?? [] as $mod)
    @php
        $mod = is_array($mod) ? $mod : [];
        $mkey = $mod['module_key'] ?? '';
        $issues = array_slice($mod['issues'] ?? [], 0, $modRowCap);
    @endphp
    <div class="mod-shell">
        <div class="mod-title">{{ $mod['module_title'] ?? $mkey }}</div>
        @if(!empty($mod['card']) && is_array($mod['card']))
            <table class="kv-grid" cellspacing="0">
                @if(($mod['module_key'] ?? '') === 'spelling_grammar')
                    <tr>
                        <td>Pages w/ issues</td><td>{{ $fmt($mod['card']['affected_urls'] ?? null) }}</td>
                        <td>Total issues</td><td>{{ $fmt($mod['card']['overview_count'] ?? null) }}</td>
                    </tr>
                    <tr>
                        <td>High confidence</td><td>{{ $fmt($mod['card']['high_confidence_issues'] ?? null) }}</td>
                        <td>Severity W / I</td><td>{{ $fmt($mod['severity_counts']['warning'] ?? null) }} / {{ $fmt($mod['severity_counts']['info'] ?? null) }}</td>
                    </tr>
                @else
                    <tr>
                        <td>Overview count</td><td>{{ $fmt($mod['card']['overview_count'] ?? null) }}</td>
                        <td>Affected URLs</td><td>{{ $fmt($mod['card']['affected_urls'] ?? null) }}</td>
                        <td>Crit / Warn / Info</td>
                        <td>{{ $fmt($mod['severity_counts']['critical'] ?? null) }} / {{ $fmt($mod['severity_counts']['warning'] ?? null) }} / {{ $fmt($mod['severity_counts']['info'] ?? null) }}</td>
                    </tr>
                @endif
            </table>
        @endif

        @if(count($issues))
            <div class="tbl-caption">Module issues (showing {{ count($issues) }})</div>
            <table class="data-table" cellspacing="0">
                <thead>
                <tr>
                    <th>Severity</th>
                    @if($mkey === 'link_metrics')
                        <th>Equity</th>
                        <th>Type</th>
                    @elseif($mkey === 'spelling_grammar')
                        <th>Kind</th>
                        <th>Text / fix</th>
                    @else
                        <th>Issue type</th>
                    @endif
                    <th>URL</th>
                    <th>Message</th>
                </tr>
                </thead>
                <tbody>
                @foreach($issues as $is)
                    @php $is = is_array($is) ? $is : []; $dj = $is['details_json'] ?? []; @endphp
                    <tr>
                        <td>{{ strtoupper($is['severity'] ?? 'info') }}</td>
                        @if($mkey === 'link_metrics')
                            @php $eq = $dj['link_equity'] ?? []; @endphp
                            <td class="truncate">{{ strtoupper($eq['tier'] ?? '—') }} · RD {{ $fmt($eq['referring_domains'] ?? 0) }} · BL {{ $fmt($eq['backlinks'] ?? 0) }}</td>
                            <td>{{ $strTrunc($is['issue_type'] ?? '', 40) }}</td>
                        @elseif($mkey === 'spelling_grammar')
                            <td>{{ $strTrunc($dj['issue_kind'] ?? $is['issue_type'] ?? '', 32) }}</td>
                            <td class="truncate">{{ $strTrunc(($dj['issue_text'] ?? '') . (isset($dj['suggested_correction']) ? ' → ' . $dj['suggested_correction'] : ''), 100) }}</td>
                        @else
                            <td>{{ $strTrunc($is['issue_type'] ?? '', 40) }}</td>
                        @endif
                        <td class="truncate">{{ $strTrunc($is['url'] ?? '', 90) }}</td>
                        <td class="truncate">{{ $strTrunc($is['message'] ?? '', 120) }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        @endif

        @foreach(array_slice($mod['tables'] ?? [], 0, 25) as $table)
            @php
                $rows = array_slice($table['rows'] ?? [], 0, $tableRowCap);
                $keys = is_array($rows[0] ?? null) ? array_keys($rows[0]) : [];
                $keys = array_slice($keys, 0, 7);
            @endphp
            <div class="tbl-caption">{{ $table['title'] ?? ($table['key'] ?? 'Table') }} @if(count($table['rows'] ?? []) > count($rows)) (first {{ count($rows) }} of {{ count($table['rows']) }}) @endif</div>
            @if(count($keys))
                <table class="data-table" cellspacing="0">
                    <thead><tr>
                        @foreach($keys as $k)
                            <th>{{ str_replace('_', ' ', $k) }}</th>
                        @endforeach
                    </tr></thead>
                    <tbody>
                    @foreach($rows as $row)
                        @php $row = is_array($row) ? $row : []; @endphp
                        <tr>
                            @foreach($keys as $k)
                                <td class="truncate">{{ $strTrunc($row[$k] ?? '—', 140) }}</td>
                            @endforeach
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            @endif
        @endforeach

        @foreach($mod['charts'] ?? [] as $chart)
            @php $ds = $chart['dataset'] ?? null; @endphp
            <div class="tbl-caption">{{ $chart['title'] ?? 'Chart' }} ({{ $chart['type'] ?? 'data' }})</div>
            @php
                $dsAssoc = is_array($ds) && count($ds) > 0 && array_keys($ds) !== range(0, count($ds) - 1);
            @endphp
            @if($dsAssoc)
                <table class="data-table" cellspacing="0">
                    @foreach($ds as $ck => $cv)
                        <tr><th style="width:35%;">{{ str_replace('_', ' ', $ck) }}</th><td>{{ $strTrunc(is_scalar($cv) ? $cv : json_encode($cv), 200) }}</td></tr>
                    @endforeach
                </table>
            @endif
        @endforeach

        @php $recs = $mod['card']['recommended_actions'] ?? []; @endphp
        @if(is_array($recs) && count($recs))
            <div class="tbl-caption">Recommended actions</div>
            <ul class="rec-list">
                @foreach($recs as $item)
                    <li>{{ $strTrunc($item, 400) }}</li>
                @endforeach
            </ul>
        @endif
    </div>
@endforeach
