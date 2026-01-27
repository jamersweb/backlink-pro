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
        if (Schema::hasTable('keyword_opportunities')) {
            // Check if table has all required columns - if so, skip migration
            $requiredColumns = ['id', 'domain_id', 'date_range_start', 'date_range_end', 'query', 'page_url', 'page_hash', 'impressions', 'clicks', 'ctr', 'position', 'opportunity_score', 'status', 'created_at', 'updated_at'];
            $hasAllColumns = true;
            foreach ($requiredColumns as $column) {
                if (!Schema::hasColumn('keyword_opportunities', $column)) {
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
                    AND TABLE_NAME = 'keyword_opportunities' 
                    AND REFERENCED_TABLE_NAME IS NOT NULL
                ");
                
                foreach ($constraints as $constraint) {
                    // Drop existing foreign key constraint if it exists
                    DB::statement("ALTER TABLE keyword_opportunities DROP FOREIGN KEY {$constraint->CONSTRAINT_NAME}");
                }
            } catch (\Exception $e) {
                // Ignore errors if constraint doesn't exist
            }
            
            // Drop and recreate to avoid constraint conflicts
            Schema::dropIfExists('keyword_opportunities');
        }
        
        // Table doesn't exist or was dropped, create it
        Schema::create('keyword_opportunities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('domain_id')->constrained('domains')->onDelete('cascade')->index();
            $table->date('date_range_start');
            $table->date('date_range_end');
            $table->string('query')->index();
            $table->text('page_url')->nullable();
            $table->char('page_hash', 64)->nullable();
            $table->unsignedBigInteger('impressions');
            $table->unsignedBigInteger('clicks');
            $table->decimal('ctr', 6, 4);
            $table->decimal('position', 6, 2);
            $table->unsignedSmallInteger('opportunity_score')->default(0);
            $table->enum('status', ['new', 'brief_created', 'ignored'])->default('new')->index();
            $table->timestamps();

            $table->unique(['domain_id', 'date_range_start', 'date_range_end', 'query', 'page_hash'], 'unique_opportunity');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('keyword_opportunities');
    }
};
