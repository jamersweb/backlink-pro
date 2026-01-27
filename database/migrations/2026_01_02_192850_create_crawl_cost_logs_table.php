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
        if (Schema::hasTable('crawl_cost_logs')) {
            // Check if table has all required columns - if so, skip migration
            $requiredColumns = ['id', 'user_id', 'domain_id', 'task_type', 'provider_code', 'units', 'unit_name', 'estimated_cost_cents', 'context_json', 'created_at'];
            $hasAllColumns = true;
            foreach ($requiredColumns as $column) {
                if (!Schema::hasColumn('crawl_cost_logs', $column)) {
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
                    AND TABLE_NAME = 'crawl_cost_logs' 
                    AND REFERENCED_TABLE_NAME IS NOT NULL
                ");
                
                foreach ($constraints as $constraint) {
                    // Drop existing foreign key constraint if it exists
                    DB::statement("ALTER TABLE crawl_cost_logs DROP FOREIGN KEY {$constraint->CONSTRAINT_NAME}");
                }
            } catch (\Exception $e) {
                // Ignore errors if constraint doesn't exist
            }
            
            // Drop and recreate to avoid constraint conflicts
            Schema::dropIfExists('crawl_cost_logs');
        }
        
        // Table doesn't exist or was dropped, create it
        Schema::create('crawl_cost_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade')->index();
            $table->foreignId('domain_id')->constrained('domains')->onDelete('cascade')->index();
            $table->string('task_type')->index();
            $table->string('provider_code')->index();
            $table->decimal('units', 10, 4)->default(0);
            $table->string('unit_name'); // requests, pages, rows
            $table->unsignedInteger('estimated_cost_cents')->default(0);
            $table->json('context_json')->nullable();
            $table->timestamp('created_at');

            $table->index(['user_id', 'created_at']);
            $table->index(['domain_id', 'created_at']);
            $table->index(['task_type', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('crawl_cost_logs');
    }
};
