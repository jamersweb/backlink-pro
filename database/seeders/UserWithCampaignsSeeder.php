<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Plan;
use App\Models\Campaign;
use App\Models\Country;
use App\Models\State;
use App\Models\City;
use App\Models\Category;
use App\Models\AutomationTask;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UserWithCampaignsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Ensure roles exist
        Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);
        
        // Get or create a plan (use Pro plan - allows 20 campaigns)
        $plan = Plan::where('slug', 'pro')->first();
        
        if (!$plan) {
            $this->command->warn('Pro plan not found. Creating plans first...');
            $this->call(PlanSeeder::class);
            $plan = Plan::where('slug', 'pro')->first();
        }
        
        // Get or create default country/state/city (US for simplicity)
        $country = Country::firstOrCreate(['name' => 'United States']);
        
        $state = State::firstOrCreate([
            'name' => 'California',
            'country_id' => $country->id,
        ]);
        
        $city = City::firstOrCreate([
            'name' => 'Los Angeles',
            'state_id' => $state->id,
        ]);
        
        // Get or create a category (use first available or create one)
        $category = Category::first();
        if (!$category) {
            $category = Category::create([
                'name' => 'Technology',
                'slug' => 'technology',
                'status' => 'active',
            ]);
        }
        
        // Create new user
        $user = User::create([
            'name' => 'John Doe',
            'email' => 'john.doe@example.com',
            'password' => Hash::make('password123'),
            'email_verified_at' => now(),
            'plan_id' => $plan->id,
            'subscription_status' => 'active',
        ]);
        
        // Assign user role
        $user->assignRole('user');
        
        $this->command->info("✓ User created: {$user->email} (Password: password123)");
        $this->command->info("✓ Plan assigned: {$plan->name} (Subscription: active)");
        
        // Create 3 campaigns
        $campaigns = [
            [
                'name' => 'Tech Blog Outreach Campaign',
                'web_name' => 'TechBlog Pro',
                'web_url' => 'https://techblogpro.com',
                'web_keyword' => 'technology, software, innovation',
                'web_about' => 'A leading technology blog covering the latest trends in software development, AI, and digital innovation.',
                'web_target' => 'worldwide',
                'company_name' => 'Tech Solutions Inc.',
                'company_logo' => 'images/company_logo/default-logo.png', // Ensure this directory exists or use a placeholder
                'company_email_address' => 'contact@techsolutions.com',
                'company_address' => '123 Tech Street, Los Angeles, CA 90001',
                'company_number' => '+1-555-0100',
                'company_country' => $country->id,
                'company_state' => $state->id,
                'company_city' => $city->id,
                'gmail' => 'techsolutions@gmail.com',
                'password' => 'SecurePassword123!',
                'status' => Campaign::STATUS_ACTIVE,
                'category_id' => $category->id ?? null,
                'settings' => [
                    'backlink_types' => ['comment', 'profile', 'forum'],
                    'daily_limit' => 20,
                    'total_limit' => 500,
                ],
            ],
            [
                'name' => 'Digital Marketing Campaign',
                'web_name' => 'Digital Marketing Hub',
                'web_url' => 'https://digitalmarketinghub.com',
                'web_keyword' => 'digital marketing, SEO, content marketing',
                'web_about' => 'Your go-to resource for digital marketing strategies, SEO tips, and content marketing insights.',
                'web_target' => 'worldwide',
                'company_name' => 'Marketing Experts LLC',
                'company_logo' => 'images/company_logo/default-logo.png', // Ensure this directory exists or use a placeholder
                'company_email_address' => 'info@marketingexperts.com',
                'company_address' => '456 Marketing Avenue, Los Angeles, CA 90002',
                'company_number' => '+1-555-0200',
                'company_country' => $country->id,
                'company_state' => $state->id,
                'company_city' => $city->id,
                'gmail' => 'marketingexperts@gmail.com',
                'password' => 'SecurePassword123!',
                'status' => Campaign::STATUS_ACTIVE,
                'category_id' => $category->id ?? null,
                'settings' => [
                    'backlink_types' => ['comment', 'profile'],
                    'daily_limit' => 15,
                    'total_limit' => 300,
                ],
            ],
            [
                'name' => 'E-commerce Growth Campaign',
                'web_name' => 'E-Commerce Success',
                'web_url' => 'https://ecommercesuccess.com',
                'web_keyword' => 'e-commerce, online business, retail',
                'web_about' => 'Helping online retailers grow their business with proven strategies and tools.',
                'web_target' => 'worldwide',
                'company_name' => 'E-Commerce Solutions Co.',
                'company_logo' => 'images/company_logo/default-logo.png', // Ensure this directory exists or use a placeholder
                'company_email_address' => 'hello@ecommercesolutions.com',
                'company_address' => '789 Commerce Boulevard, Los Angeles, CA 90003',
                'company_number' => '+1-555-0300',
                'company_country' => $country->id,
                'company_state' => $state->id,
                'company_city' => $city->id,
                'gmail' => 'ecommercesolutions@gmail.com',
                'password' => 'SecurePassword123!',
                'status' => Campaign::STATUS_ACTIVE,
                'category_id' => $category->id ?? null,
                'settings' => [
                    'backlink_types' => ['comment', 'profile', 'forum', 'guestposting'],
                    'daily_limit' => 25,
                    'total_limit' => 750,
                ],
            ],
        ];
        
        foreach ($campaigns as $campaignData) {
            $campaignData['user_id'] = $user->id;
            $campaign = Campaign::create($campaignData);
            $this->command->info("✓ Campaign created: {$campaign->name}");
            
            // Create automation tasks automatically for active campaigns
            if ($campaign->status === Campaign::STATUS_ACTIVE) {
                $tasksCreated = $this->createTasksForCampaign($campaign, $plan);
                $this->command->info("  → Created {$tasksCreated} automation task(s)");
            }
        }
        
        $this->command->info("\n✅ Seeding completed!");
        $this->command->info("User: {$user->email}");
        $this->command->info("Plan: {$plan->name} ({$plan->price}/month)");
        $this->command->info("Campaigns: " . count($campaigns));
        
        // Show total tasks created
        $totalTasks = AutomationTask::whereIn('campaign_id', Campaign::where('user_id', $user->id)->pluck('id'))->count();
        $this->command->info("Total Tasks Created: {$totalTasks}");
    }
    
    /**
     * Create automation tasks for a campaign based on plan settings
     */
    protected function createTasksForCampaign(Campaign $campaign, Plan $plan): int
    {
        if (!$plan) {
            return 0;
        }

        // Get backlink types from plan (use plan's backlink types, not campaign settings)
        $backlinkTypes = $plan->backlink_types ?? ['comment', 'profile'];
        
        if (empty($backlinkTypes)) {
            return 0;
        }
        
        // Calculate initial tasks per type (based on daily limit)
        $dailyLimit = $plan->daily_backlink_limit ?? 10;
        $tasksPerType = max(1, floor($dailyLimit / count($backlinkTypes)));

        // Handle keywords - convert string to array if needed
        $keywords = $campaign->web_keyword ?? '';
        if (is_string($keywords)) {
            $keywords = !empty($keywords) ? explode(',', $keywords) : [];
            $keywords = array_map('trim', $keywords);
            $keywords = array_filter($keywords);
        }
        if (empty($keywords)) {
            $keywords = [$campaign->web_name ?? 'SEO'];
        }

        $settings = $campaign->settings ?? [];
        if (!is_array($settings)) {
            $settings = json_decode($settings, true) ?? [];
        }

        $totalCreated = 0;

        // Create tasks for each backlink type
        foreach ($backlinkTypes as $type) {
            // Check if plan allows this backlink type
            if (!$plan->allowsBacklinkType($type)) {
                continue;
            }

            // Map 'guestposting' to 'guest' (task type enum uses 'guest')
            $taskType = $type === 'guestposting' ? 'guest' : $type;

            // Create initial batch of tasks
            for ($i = 0; $i < $tasksPerType; $i++) {
                AutomationTask::create([
                    'campaign_id' => $campaign->id,
                    'type' => $taskType,
                    'status' => AutomationTask::STATUS_PENDING,
                    'payload' => [
                        'campaign_id' => $campaign->id,
                        'keywords' => $keywords,
                        'anchor_text_strategy' => $settings['anchor_text_strategy'] ?? 'variation',
                        'content_tone' => $settings['content_tone'] ?? 'professional',
                    ],
                    'max_retries' => 3,
                    'retry_count' => 0,
                ]);
                $totalCreated++;
            }
        }

        return $totalCreated;
    }
}

