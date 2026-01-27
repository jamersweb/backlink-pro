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
        if (Schema::hasTable('backlink_ref_domains')) {
            // Check if table has all required columns - if so, skip migration
            $requiredColumns = ['id', 'domain_id', 'ref_domain', 'first_seen_at', 'last_seen_at', 'links_count', 'follow_links_count', 'metrics_json', 'quality_score', 'risk_score', 'status', 'notes', 'created_at', 'updated_at'];
            $hasAllColumns = true;
            foreach ($requiredColumns as $column) {
                if (!Schema::hasColumn('backlink_ref_domains', $column)) {
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
                    AND TABLE_NAME = 'backlink_ref_domains' 
                    AND REFERENCED_TABLE_NAME IS NOT NULL
                ");
                
                foreach ($constraints as $constraint) {
                    // Drop existing foreign key constraint if it exists
                    DB::statement("ALTER TABLE backlink_ref_domains DROP FOREIGN KEY {$constraint->CONSTRAINT_NAME}");
                }
            } catch (\Exception $e) {
                // Ignore errors if constraint doesn't exist
            }
            
            // Drop and recreate to avoid constraint conflicts
            Schema::dropIfExists('backlink_ref_domains');
        }
        
        // Table doesn't exist or was dropped, create it
        Schema::create('backlink_ref_domains', function (Blueprint $table) {
            $table->id();
            $table->foreignId('domain_id')->constrained('domains')->onDelete('cascade')->index();
            $table->string('ref_domain')->index();
            $table->timestamp('first_seen_at')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->unsignedInteger('links_count')->default(0);
            $table->unsignedInteger('follow_links_count')->default(0);
            $table->json('metrics_json')->nullable(); // provider metrics (traffic, spam score etc)
            $table->unsignedSmallInteger('quality_score')->default(0); // 0..100
            $table->unsignedSmallInteger('risk_score')->default(0); // 0..100
            $table->enum('status', ['ok', 'review', 'toxic', 'disavowed'])->default('ok')->index();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['domain_id', 'ref_domain']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('backlink_ref_domains');
    }
};
