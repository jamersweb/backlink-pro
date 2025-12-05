<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Seed roles first (required for user registration)
        $this->call(RoleSeeder::class);
        
        // Seed admin user
        $this->call(AdminSeeder::class);
        
        // Seed categories and subcategories
        $this->call(CategorySeeder::class);

        // Create test user
        $testUser = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);
        
        // Assign user role to test user
        if (!$testUser->hasRole('user')) {
            $testUser->assignRole('user');
        }
    }
}
