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
        // Check if table already exists (from a previous partial migration)
        if (Schema::hasTable('domain_provider_preferences')) {
            // Check if table has all required columns - if so, skip migration
            $requiredColumns = ['id', 'domain_id', 'task_type', 'provider_code', 'fallback_codes_json', 'created_at', 'updated_at'];
            $hasAllColumns = true;
            foreach ($requiredColumns as $column) {
                if (!Schema::hasColumn('domain_provider_preferences', $column)) {
                    $hasAllColumns = false;
                    break;
                }
            }
            
            if ($hasAllColumns) {
                // Table structure is complete, skip migration
                return;
            }
            
            // Table exists but is incomplete
            // Check for existing foreign key constraints and drop them if they conflict
            try {
                $constraints = DB::select("
                    SELECT CONSTRAINT_NAME 
                    FROM information_schema.KEY_COLUMN_USAGE 
                    WHERE TABLE_SCHEMA = DATABASE() 
                    AND TABLE_NAME = 'domain_provider_preferences' 
                    AND COLUMN_NAME = 'domain_id' 
                    AND REFERENCED_TABLE_NAME IS NOT NULL
                ");
                
                foreach ($constraints as $constraint) {
                    // Drop existing foreign key constraint if it exists
                    DB::statement("ALTER TABLE domain_provider_preferences DROP FOREIGN KEY {$constraint->CONSTRAINT_NAME}");
                }
            } catch (\Exception $e) {
                // Ignore errors if constraint doesn't exist
            }
            
            // Drop and recreate to avoid constraint conflicts
            Schema::dropIfExists('domain_provider_preferences');
        }
        
        // Table doesn't exist or was dropped, create it
        Schema::create('domain_provider_preferences', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('domain_id');
            $table->string('task_type')->index(); // speed.pagespeed, crawl.http_basic, backlinks.provider
            $table->string('provider_code')->index();
            $table->json('fallback_codes_json')->nullable();
            $table->timestamps();

            // Add foreign key with explicit name to avoid conflicts
            $table->foreign('domain_id', 'domain_provider_preferences_domain_id_foreign')
                  ->references('id')
                  ->on('domains')
                  ->onDelete('cascade');
            
            $table->index('domain_id');
            $table->unique(['domain_id', 'task_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('domain_provider_preferences');
    }
};
