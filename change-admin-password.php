<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Hash;

// Get new password from command line argument or prompt
$newPassword = $argv[1] ?? null;

if (!$newPassword) {
    echo "Usage: php change-admin-password.php <new-password>\n";
    echo "Example: php change-admin-password.php MyNewPassword123!\n";
    exit(1);
}

// Find admin user
$admin = User::where('email', 'admin@example.com')->first();

if (!$admin) {
    echo "Admin user not found (email: admin@example.com)\n";
    exit(1);
}

// Update password
$admin->password = Hash::make($newPassword);
$admin->save();

echo "✓ Admin password updated successfully!\n";
echo "Email: admin@example.com\n";
echo "New Password: {$newPassword}\n";

