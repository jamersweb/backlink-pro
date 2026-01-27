<?php

namespace App\Http\Controllers;

use App\Models\Domain;
use App\Models\SnippetInstallation;
use App\Models\SnippetEvent;
use App\Models\SnippetPerformance;
use App\Models\SnippetCommand;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SnippetAgentController extends Controller
{
    /**
     * Handle installation ping
     */
    public function ping(Request $request, string $key)
    {
        $domain = $this->validateKey($key);
        if (!$domain) {
            return response()->json(['error' => 'Invalid key'], 404);
        }

        if (!$this->validateOrigin($request, $domain)) {
            return response()->json(['error' => 'Origin mismatch'], 403);
        }

        $data = $request->validate([
            'path' => 'required|string',
            'origin_host' => 'nullable|string',
            'agent_version' => 'nullable|string',
        ]);

        $installation = SnippetInstallation::updateOrCreate(
            ['domain_id' => $domain->id],
            [
                'key' => $key,
                'status' => SnippetInstallation::STATUS_VERIFIED,
                'last_seen_at' => now(),
                'last_origin_host' => $data['origin_host'] ?? null,
                'agent_version' => $data['agent_version'] ?? null,
                'settings_json' => ['tracking' => true, 'performance' => false],
            ]
        );

        if (!$installation->first_seen_at) {
            $installation->update(['first_seen_at' => now()]);
        }

        return response()->json(['status' => 'ok']);
    }

    /**
     * Handle pageview event
     */
    public function event(Request $request, string $key)
    {
        $domain = $this->validateKey($key);
        if (!$domain) {
            return response()->json(['error' => 'Invalid key'], 404);
        }

        $installation = SnippetInstallation::where('domain_id', $domain->id)->first();
        if (!$installation || !($installation->settings_json['tracking'] ?? false)) {
            return response()->json(['status' => 'disabled'], 200);
        }

        $data = $request->validate([
            'path' => 'required|string',
            'ref_host' => 'nullable|string',
            'ua_hash' => 'nullable|string',
            'day_bucket' => 'nullable|date',
        ]);

        $date = $data['day_bucket'] ?? now()->toDateString();
        $path = $this->normalizePath($data['path']);

        // Upsert event
        $event = SnippetEvent::updateOrInsert(
            [
                'domain_id' => $domain->id,
                'date' => $date,
                'path' => $path,
            ],
            [
                'views' => DB::raw('views + 1'),
                'updated_at' => now(),
            ]
        );

        // Track unique (approximate using IP hash)
        $ipHash = hash('sha256', $request->ip() . $request->userAgent());
        $uniqueKey = "snippet:unique:{$domain->id}:{$date}:{$path}";
        
        if (!Cache::has($uniqueKey . ':' . $ipHash)) {
            Cache::put($uniqueKey . ':' . $ipHash, true, now()->addDay());
            SnippetEvent::where('domain_id', $domain->id)
                ->where('date', $date)
                ->where('path', $path)
                ->increment('uniques');
        }

        return response()->json(['status' => 'ok']);
    }

    /**
     * Handle performance metrics
     */
    public function perf(Request $request, string $key)
    {
        $domain = $this->validateKey($key);
        if (!$domain) {
            return response()->json(['error' => 'Invalid key'], 404);
        }

        $installation = SnippetInstallation::where('domain_id', $domain->id)->first();
        if (!$installation || !($installation->settings_json['performance'] ?? false)) {
            return response()->json(['status' => 'disabled'], 200);
        }

        $data = $request->validate([
            'path' => 'required|string',
            'load_ms' => 'nullable|integer|min:0',
            'ttfb_ms' => 'nullable|integer|min:0',
        ]);

        $date = now()->toDateString();
        $path = $this->normalizePath($data['path']);

        // Update running average
        $perf = SnippetPerformance::firstOrNew([
            'domain_id' => $domain->id,
            'date' => $date,
            'path' => $path,
        ]);

        if ($perf->exists) {
            // Running average
            $samples = $perf->samples + 1;
            if ($data['load_ms']) {
                $perf->avg_load_ms = (int) round(($perf->avg_load_ms * $perf->samples + $data['load_ms']) / $samples);
            }
            if ($data['ttfb_ms']) {
                $perf->avg_ttfb_ms = (int) round(($perf->avg_ttfb_ms * $perf->samples + $data['ttfb_ms']) / $samples);
            }
            $perf->samples = $samples;
        } else {
            $perf->avg_load_ms = $data['load_ms'] ?? null;
            $perf->avg_ttfb_ms = $data['ttfb_ms'] ?? null;
            $perf->samples = 1;
        }

        $perf->save();

        return response()->json(['status' => 'ok']);
    }

    /**
     * Get queued commands
     */
    public function commands(Request $request, string $key)
    {
        $domain = $this->validateKey($key);
        if (!$domain) {
            return response()->json(['error' => 'Invalid key'], 404);
        }

        $commands = SnippetCommand::where('domain_id', $domain->id)
            ->where('status', SnippetCommand::STATUS_QUEUED)
            ->orderBy('created_at')
            ->limit(10)
            ->get()
            ->map(function($cmd) {
                return [
                    'id' => $cmd->id,
                    'command' => $cmd->command,
                    'payload' => $cmd->payload_json,
                ];
            });

        return response()->json(['commands' => $commands]);
    }

    /**
     * Acknowledge command
     */
    public function ackCommand(Request $request, string $key, int $commandId)
    {
        $domain = $this->validateKey($key);
        if (!$domain) {
            return response()->json(['error' => 'Invalid key'], 404);
        }

        $command = SnippetCommand::where('id', $commandId)
            ->where('domain_id', $domain->id)
            ->first();

        if (!$command) {
            return response()->json(['error' => 'Command not found'], 404);
        }

        $data = $request->validate([
            'status' => 'required|in:delivered,completed',
        ]);

        $command->update(['status' => $data['status']]);

        return response()->json(['status' => 'ok']);
    }

    /**
     * Validate snippet key
     */
    protected function validateKey(string $key): ?Domain
    {
        return Domain::where('meta_snippet_key', $key)->first();
    }

    /**
     * Validate origin host
     */
    protected function validateOrigin(Request $request, Domain $domain): bool
    {
        $origin = $request->header('Origin') ?? $request->header('Referer');
        if (!$origin) {
            // Allow but log for review
            return true;
        }

        $originHost = parse_url($origin, PHP_URL_HOST);
        $domainHost = $domain->host;

        // Remove www. prefix for comparison
        $originHost = preg_replace('/^www\./', '', $originHost);
        $domainHost = preg_replace('/^www\./', '', $domainHost);

        return strtolower($originHost) === strtolower($domainHost);
    }

    /**
     * Normalize path
     */
    protected function normalizePath(string $path): string
    {
        $path = parse_url($path, PHP_URL_PATH) ?? $path;
        $path = '/' . ltrim($path, '/');
        return $path === '/' ? '/' : rtrim($path, '/');
    }
}
