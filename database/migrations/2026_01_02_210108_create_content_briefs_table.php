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
        if (Schema::hasTable('content_briefs')) {
            // Check if table has all required columns - if so, skip migration
            $requiredColumns = ['id', 'domain_id', 'user_id', 'title', 'primary_keyword', 'secondary_keywords_json', 'target_type', 'target_url', 'suggested_slug', 'intent', 'outline_json', 'faq_json', 'internal_links_json', 'meta_suggestion_json', 'status', 'created_at', 'updated_at'];
            $hasAllColumns = true;
            foreach ($requiredColumns as $column) {
                if (!Schema::hasColumn('content_briefs', $column)) {
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
                    AND TABLE_NAME = 'content_briefs' 
                    AND REFERENCED_TABLE_NAME IS NOT NULL
                ");
                
                foreach ($constraints as $constraint) {
                    // Drop existing foreign key constraint if it exists
                    DB::statement("ALTER TABLE content_briefs DROP FOREIGN KEY {$constraint->CONSTRAINT_NAME}");
                }
            } catch (\Exception $e) {
                // Ignore errors if constraint doesn't exist
            }
            
            // Drop and recreate to avoid constraint conflicts
            Schema::dropIfExists('content_briefs');
        }
        
        // Table doesn't exist or was dropped, create it
        Schema::create('content_briefs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('domain_id');
            $table->unsignedBigInteger('user_id');
            $table->string('title');
            $table->string('primary_keyword')->index();
            $table->json('secondary_keywords_json')->nullable();
            $table->enum('target_type', ['existing_page', 'new_page'])->index();
            $table->text('target_url')->nullable();
            $table->string('suggested_slug')->nullable();
            $table->enum('intent', ['informational', 'transactional', 'navigational', 'mixed'])->default('informational');
            $table->json('outline_json');
            $table->json('faq_json')->nullable();
            $table->json('internal_links_json')->nullable();
            $table->json('meta_suggestion_json')->nullable();
            $table->enum('status', ['draft', 'writing', 'published', 'archived'])->default('draft')->index();
            $table->timestamps();

            // Add foreign keys with explicit names to avoid conflicts
            $table->foreign('domain_id', 'content_briefs_domain_id_foreign')
                  ->references('id')
                  ->on('domains')
                  ->onDelete('cascade');
            
            $table->foreign('user_id', 'content_briefs_user_id_foreign')
                  ->references('id')
                  ->on('users')
                  ->onDelete('cascade');
            
            $table->index('domain_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('content_briefs');
    }
};
