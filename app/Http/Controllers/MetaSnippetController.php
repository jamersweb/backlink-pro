<?php

namespace App\Http\Controllers;

use App\Models\Domain;
use App\Models\DomainMetaPage;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class MetaSnippetController extends Controller
{
    /**
     * Serve the JavaScript snippet
     */
    public function script(string $snippetKey)
    {
        $domain = Domain::where('meta_snippet_key', $snippetKey)->first();

        if (!$domain) {
            abort(404);
        }

        $appUrl = config('app.url');
        $agentVersion = '1.0.0';
        $script = <<<JS
(function() {
    'use strict';
    var AGENT_VERSION = '{$agentVersion}';
    var APP_URL = '{$appUrl}';
    var KEY = '{$snippetKey}';
    var path = window.location.pathname;
    var originHost = window.location.host;
    
    // Agent state
    var agentState = {
        initialized: false,
        lastPing: null,
        settings: {tracking: true, performance: false}
    };
    
    // Ping server (installation verification)
    function ping() {
        var now = Date.now();
        if (agentState.lastPing && (now - agentState.lastPing) < 21600000) { // 6 hours
            return;
        }
        agentState.lastPing = now;
        
        fetch(APP_URL + '/snippet/' + KEY + '/ping', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                path: path,
                origin_host: originHost,
                agent_version: AGENT_VERSION
            })
        }).catch(function() {});
    }
    
    // Send pageview event
    function trackEvent() {
        if (!agentState.settings.tracking) return;
        
        var refHost = document.referrer ? new URL(document.referrer).host : null;
        var ua = navigator.userAgent;
        var uaHash = btoa(ua).substring(0, 16); // Simple hash
        
        fetch(APP_URL + '/snippet/' + KEY + '/event', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                path: path,
                ref_host: refHost,
                ua_hash: uaHash,
                day_bucket: new Date().toISOString().split('T')[0]
            })
        }).catch(function() {});
    }
    
    // Send performance metrics
    function trackPerformance() {
        if (!agentState.settings.performance || !window.performance) return;
        
        var perf = window.performance.timing;
        var loadMs = perf.loadEventEnd - perf.navigationStart;
        var ttfbMs = perf.responseStart - perf.navigationStart;
        
        fetch(APP_URL + '/snippet/' + KEY + '/perf', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                path: path,
                load_ms: loadMs,
                ttfb_ms: ttfbMs
            })
        }).catch(function() {});
    }
    
    // Check for commands
    function checkCommands() {
        fetch(APP_URL + '/snippet/' + KEY + '/commands')
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (data.commands && data.commands.length > 0) {
                    data.commands.forEach(function(cmd) {
                        handleCommand(cmd);
                    });
                }
            })
            .catch(function() {});
    }
    
    // Handle remote command
    function handleCommand(cmd) {
        if (cmd.command === 'refresh_meta') {
            loadMeta();
            ackCommand(cmd.id, 'completed');
        } else if (cmd.command === 'verify') {
            ping();
            ackCommand(cmd.id, 'completed');
        }
    }
    
    // Acknowledge command
    function ackCommand(cmdId, status) {
        fetch(APP_URL + '/snippet/' + KEY + '/commands/' + cmdId + '/ack', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({status: status})
        }).catch(function() {});
    }
    
    // Load and inject meta tags
    function loadMeta() {
        var script = document.createElement('script');
        script.src = APP_URL + '/snippet/' + KEY + '/meta?path=' + encodeURIComponent(path);
        script.onload = function() {
            if (window.backlinkProMeta) {
                var meta = window.backlinkProMeta;
                if (meta.title) {
                    document.title = meta.title;
                    var titleTag = document.querySelector('title');
                    if (titleTag) titleTag.textContent = meta.title;
                }
                if (meta.description) {
                    var descTag = document.querySelector('meta[name="description"]');
                    if (descTag) {
                        descTag.setAttribute('content', meta.description);
                    } else {
                        var metaDesc = document.createElement('meta');
                        metaDesc.name = 'description';
                        metaDesc.content = meta.description;
                        document.head.appendChild(metaDesc);
                    }
                }
                if (meta.og_title) {
                    var ogTitle = document.querySelector('meta[property="og:title"]');
                    if (ogTitle) {
                    ogTitle.setAttribute('content', meta.og_title);
                } else {
                    var metaOgTitle = document.createElement('meta');
                    metaOgTitle.setAttribute('property', 'og:title');
                    metaOgTitle.content = meta.og_title;
                    document.head.appendChild(metaOgTitle);
                }
            }
            if (meta.og_description) {
                var ogDesc = document.querySelector('meta[property="og:description"]');
                if (ogDesc) {
                    ogDesc.setAttribute('content', meta.og_description);
                } else {
                    var metaOgDesc = document.createElement('meta');
                    metaOgDesc.setAttribute('property', 'og:description');
                    metaOgDesc.content = meta.og_description;
                    document.head.appendChild(metaOgDesc);
                }
            }
            if (meta.og_image) {
                var ogImage = document.querySelector('meta[property="og:image"]');
                if (ogImage) {
                    ogImage.setAttribute('content', meta.og_image);
                } else {
                    var metaOgImage = document.createElement('meta');
                    metaOgImage.setAttribute('property', 'og:image');
                    metaOgImage.content = meta.og_image;
                    document.head.appendChild(metaOgImage);
                }
            }
            if (meta.canonical) {
                var canonical = document.querySelector('link[rel="canonical"]');
                if (canonical) {
                    canonical.href = meta.canonical;
                } else {
                    var linkCanonical = document.createElement('link');
                    linkCanonical.rel = 'canonical';
                    linkCanonical.href = meta.canonical;
                    document.head.appendChild(linkCanonical);
                }
            }
            if (meta.robots) {
                var robots = document.querySelector('meta[name="robots"]');
                if (robots) {
                    robots.setAttribute('content', meta.robots);
                } else {
                    var metaRobots = document.createElement('meta');
                    metaRobots.name = 'robots';
                    metaRobots.content = meta.robots;
                    document.head.appendChild(metaRobots);
                }
            }
        }
    };
    document.head.appendChild(script);
    
    // Initialize agent
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            ping();
            trackEvent();
            checkCommands();
            if (agentState.settings.performance) {
                window.addEventListener('load', function() {
                    trackPerformance();
                });
            }
        });
    } else {
        ping();
        trackEvent();
        checkCommands();
        if (agentState.settings.performance) {
            window.addEventListener('load', function() {
                trackPerformance();
            });
        }
    }
    
    // Check for commands periodically (once per page load, then every 60s)
    setInterval(checkCommands, 60000);
    
    agentState.initialized = true;
})();
JS;

        return response($script, 200)
            ->header('Content-Type', 'application/javascript')
            ->header('Cache-Control', 'public, max-age=3600');
    }

    /**
     * Serve meta JSON for a specific path
     */
    public function metaJson(Request $request, string $snippetKey)
    {
        $domain = Domain::where('meta_snippet_key', $snippetKey)->first();

        if (!$domain) {
            return response()->json(['error' => 'Not found'], 404);
        }

        $path = $request->query('path', '/');

        // Find page by path
        $page = DomainMetaPage::where('domain_id', $domain->id)
            ->where(function($q) use ($path) {
                $q->where('path', $path)
                  ->orWhere('path', rtrim($path, '/'))
                  ->orWhere('path', $path . '/');
            })
            ->first();

        if (!$page || !$page->meta_published_json) {
            return response()->json([
                'title' => '',
                'description' => '',
                'og_title' => '',
                'og_description' => '',
                'og_image' => '',
                'canonical' => '',
                'robots' => 'index,follow',
            ]);
        }

        $meta = $page->meta_published_json;

        return response()->json([
            'title' => $meta['title'] ?? '',
            'description' => $meta['description'] ?? '',
            'og_title' => $meta['og_title'] ?? '',
            'og_description' => $meta['og_description'] ?? '',
            'og_image' => $meta['og_image'] ?? '',
            'canonical' => $meta['canonical'] ?? '',
            'robots' => $meta['robots'] ?? 'index,follow',
        ])
        ->header('Access-Control-Allow-Origin', '*')
        ->header('Access-Control-Allow-Methods', 'GET')
        ->header('Cache-Control', 'public, max-age=60');
    }
}
