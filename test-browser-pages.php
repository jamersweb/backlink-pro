<?php

/**
 * Browser Page Testing Script
 * Tests all frontend pages are accessible
 * 
 * Usage: php test-browser-pages.php
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Http;

$baseUrl = 'http://127.0.0.1:8000';

$pages = [
    // Public pages
    ['name' => 'Homepage', 'url' => '/', 'requires_auth' => false],
    ['name' => 'Pricing', 'url' => '/pricing', 'requires_auth' => false],
    ['name' => 'Login', 'url' => '/login', 'requires_auth' => false],
    ['name' => 'Register', 'url' => '/register', 'requires_auth' => false],
    ['name' => 'About', 'url' => '/about', 'requires_auth' => false],
    ['name' => 'Features', 'url' => '/features', 'requires_auth' => false],
    ['name' => 'Contact', 'url' => '/contact', 'requires_auth' => false],
    ['name' => 'Blog', 'url' => '/blog', 'requires_auth' => false],
    ['name' => 'Help', 'url' => '/help', 'requires_auth' => false],
    ['name' => 'Documentation', 'url' => '/documentation', 'requires_auth' => false],
    
    // User pages (require auth - will redirect to login)
    ['name' => 'Dashboard', 'url' => '/dashboard', 'requires_auth' => true],
    ['name' => 'Campaigns', 'url' => '/campaign', 'requires_auth' => true],
    ['name' => 'Backlinks', 'url' => '/backlinks', 'requires_auth' => true],
    ['name' => 'Reports', 'url' => '/reports', 'requires_auth' => true],
    ['name' => 'Domains', 'url' => '/domains', 'requires_auth' => true],
    ['name' => 'Settings', 'url' => '/settings', 'requires_auth' => true],
    ['name' => 'Gmail', 'url' => '/gmail', 'requires_auth' => true],
    
    // Admin pages (require admin auth - will redirect)
    ['name' => 'Admin Dashboard', 'url' => '/admin/dashboard', 'requires_auth' => true, 'admin' => true],
    ['name' => 'Admin Users', 'url' => '/admin/users', 'requires_auth' => true, 'admin' => true],
    ['name' => 'Admin Plans', 'url' => '/admin/plans', 'requires_auth' => true, 'admin' => true],
    ['name' => 'Admin Campaigns', 'url' => '/admin/campaigns', 'requires_auth' => true, 'admin' => true],
    ['name' => 'Admin Backlinks', 'url' => '/admin/backlinks', 'requires_auth' => true, 'admin' => true],
    ['name' => 'Admin Tasks', 'url' => '/admin/automation-tasks', 'requires_auth' => true, 'admin' => true],
    ['name' => 'Admin Proxies', 'url' => '/admin/proxies', 'requires_auth' => true, 'admin' => true],
    ['name' => 'Admin System Health', 'url' => '/admin/system-health', 'requires_auth' => true, 'admin' => true],
    ['name' => 'Admin Captcha Logs', 'url' => '/admin/captcha-logs', 'requires_auth' => true, 'admin' => true],
];

$results = [];

echo "========================================\n";
echo "BROWSER PAGE TESTING\n";
echo "========================================\n\n";
echo "Base URL: $baseUrl\n\n";

foreach ($pages as $page) {
    $name = $page['name'];
    $url = $page['url'];
    $fullUrl = $baseUrl . $url;
    
    echo "Testing: $name\n";
    echo "  URL: $url\n";
    
    try {
        $response = Http::timeout(10)->get($fullUrl, [
            'allow_redirects' => true,
        ]);
        
        $statusCode = $response->status();
        $isAuthRequired = $page['requires_auth'] ?? false;
        
        // For auth-required pages, 302 redirect to login is expected if not logged in
        $isSuccess = $statusCode === 200 || ($statusCode === 302 && $isAuthRequired);
        
        $result = [
            'name' => $name,
            'url' => $url,
            'status_code' => $statusCode,
            'success' => $isSuccess,
            'redirects_to_login' => $statusCode === 302 && $isAuthRequired,
        ];
        
        $results[] = $result;
        
        if ($isSuccess) {
            if ($result['redirects_to_login']) {
                echo "  ✅ Status: $statusCode (Redirects to login as expected)\n";
            } else {
                echo "  ✅ Status: $statusCode\n";
            }
        } else {
            echo "  ❌ Status: $statusCode\n";
        }
        
        echo "\n";
        
    } catch (Exception $e) {
        echo "  ❌ Exception: " . $e->getMessage() . "\n\n";
        $results[] = [
            'name' => $name,
            'url' => $url,
            'success' => false,
            'error' => $e->getMessage(),
        ];
    }
}

// Summary
echo "========================================\n";
echo "TEST SUMMARY\n";
echo "========================================\n\n";

$passed = count(array_filter($results, fn($r) => $r['success'] ?? false));
$failed = count($results) - $passed;

echo "Total Pages: " . count($results) . "\n";
echo "Accessible: $passed\n";
echo "Failed: $failed\n\n";

if ($failed > 0) {
    echo "Failed Pages:\n";
    foreach ($results as $result) {
        if (!($result['success'] ?? false)) {
            echo "  ❌ {$result['name']} ({$result['url']})\n";
            if (isset($result['error'])) {
                echo "     Error: {$result['error']}\n";
            } elseif (isset($result['status_code'])) {
                echo "     Status: {$result['status_code']}\n";
            }
        }
    }
}

echo "\n";


