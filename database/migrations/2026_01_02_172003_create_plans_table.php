<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Check if table already exists (from older migration)
        if (Schema::hasTable('plans')) {
            // Table exists, check if it already has the new schema columns
            if (Schema::hasColumn('plans', 'code') && Schema::hasColumn('plans', 'limits_json')) {
                // New schema already exists, skip migration
                return;
            }
            
            // Table exists with old schema, add new columns if they don't exist
            // Use raw SQL to avoid Blueprint closure issues with immediate data updates
            
            // Add code column if it doesn't exist
            if (!Schema::hasColumn('plans', 'code')) {
                if (Schema::hasColumn('plans', 'slug')) {
                    // Add code column, copy from slug, then make it unique and not null
                    DB::statement('ALTER TABLE plans ADD COLUMN code VARCHAR(255) NULL AFTER name');
                    DB::statement('UPDATE plans SET code = slug');
                    DB::statement('ALTER TABLE plans MODIFY COLUMN code VARCHAR(255) NOT NULL');
                    // Check if unique key already exists before adding
                    $indexes = DB::select("SHOW INDEXES FROM plans WHERE Key_name = 'plans_code_unique'");
                    if (empty($indexes)) {
                        DB::statement('ALTER TABLE plans ADD UNIQUE KEY plans_code_unique (code)');
                    }
                } else {
                    Schema::table('plans', function (Blueprint $table) {
                        $table->string('code')->unique()->after('name');
                    });
                }
            }
            
            // Add price_monthly column if it doesn't exist
            if (!Schema::hasColumn('plans', 'price_monthly')) {
                if (Schema::hasColumn('plans', 'price')) {
                    DB::statement('ALTER TABLE plans ADD COLUMN price_monthly INT UNSIGNED NULL AFTER code');
                    DB::statement('UPDATE plans SET price_monthly = CAST(price * 100 AS UNSIGNED) WHERE price_monthly IS NULL');
                } else {
                    Schema::table('plans', function (Blueprint $table) {
                        $table->unsignedInteger('price_monthly')->nullable()->after('code');
                    });
                }
            }
            
            // Add limits_json column if it doesn't exist
            if (!Schema::hasColumn('plans', 'limits_json')) {
                DB::statement('ALTER TABLE plans ADD COLUMN limits_json JSON NULL AFTER price_monthly');
                // Migrate old limit columns to JSON if they exist
                if (Schema::hasColumn('plans', 'max_domains') || Schema::hasColumn('plans', 'daily_backlink_limit')) {
                    DB::statement("UPDATE plans SET limits_json = JSON_OBJECT(
                        'max_domains', COALESCE(max_domains, 1),
                        'max_campaigns', COALESCE(max_campaigns, 1),
                        'daily_backlink_limit', COALESCE(daily_backlink_limit, 10)
                    ) WHERE limits_json IS NULL");
                } else {
                    DB::statement("UPDATE plans SET limits_json = '{}' WHERE limits_json IS NULL");
                }
                // Make it not null after data migration
                DB::statement('ALTER TABLE plans MODIFY COLUMN limits_json JSON NOT NULL');
            }
            
            // Add features_json column if it doesn't exist
            if (!Schema::hasColumn('plans', 'features_json')) {
                if (Schema::hasColumn('plans', 'features')) {
                    DB::statement('ALTER TABLE plans ADD COLUMN features_json JSON NULL AFTER limits_json');
                    DB::statement('UPDATE plans SET features_json = features WHERE features_json IS NULL');
                } else {
                    Schema::table('plans', function (Blueprint $table) {
                        $table->json('features_json')->nullable()->after('limits_json');
                    });
                }
            }
        } else {
            // Table doesn't exist, create it
            Schema::create('plans', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('code')->unique();
                $table->unsignedInteger('price_monthly')->nullable(); // in cents
                $table->json('limits_json');
                $table->json('features_json')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plans');
    }
};
