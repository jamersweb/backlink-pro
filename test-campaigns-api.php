<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Http\Controllers\Admin\CampaignController;
use Illuminate\Http\Request;

$controller = new CampaignController();
$request = Request::create('/admin/campaigns', 'GET');

// Simulate authenticated admin user
$admin = \App\Models\User::where('email', 'admin@example.com')->first();
if ($admin) {
    auth()->login($admin);
}

$response = $controller->index($request);
$data = $response->getData(true);

echo "=== Campaigns API Test ===\n\n";
echo "Stats:\n";
print_r($data['props']['stats'] ?? []);
echo "\nCampaigns Count: " . count($data['props']['campaigns']['data'] ?? []) . "\n";
echo "Total: " . ($data['props']['campaigns']['total'] ?? 0) . "\n\n";

if (isset($data['props']['campaigns']['data']) && count($data['props']['campaigns']['data']) > 0) {
    echo "Campaigns:\n";
    foreach ($data['props']['campaigns']['data'] as $campaign) {
        echo "- {$campaign['name']} (ID: {$campaign['id']}, Status: {$campaign['status']})\n";
    }
} else {
    echo "No campaigns in response!\n";
    echo "Response structure:\n";
    print_r(array_keys($data['props'] ?? []));
}

