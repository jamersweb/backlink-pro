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
        if (Schema::hasTable('snippet_events')) {
            // Check if table has all required columns - if so, skip migration
            $requiredColumns = ['id', 'domain_id', 'date', 'path', 'views', 'uniques', 'created_at', 'updated_at'];
            $hasAllColumns = true;
            foreach ($requiredColumns as $column) {
                if (!Schema::hasColumn('snippet_events', $column)) {
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
                    AND TABLE_NAME = 'snippet_events' 
                    AND REFERENCED_TABLE_NAME IS NOT NULL
                ");
                
                foreach ($constraints as $constraint) {
                    // Drop existing foreign key constraint if it exists
                    DB::statement("ALTER TABLE snippet_events DROP FOREIGN KEY {$constraint->CONSTRAINT_NAME}");
                }
            } catch (\Exception $e) {
                // Ignore errors if constraint doesn't exist
            }
            
            // Drop and recreate to avoid constraint conflicts
            Schema::dropIfExists('snippet_events');
        }
        
        // Table doesn't exist or was dropped, create it
        Schema::create('snippet_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('domain_id')->constrained('domains')->onDelete('cascade')->index();
            $table->date('date')->index();
            $table->string('path')->index();
            $table->unsignedBigInteger('views')->default(0);
            $table->unsignedBigInteger('uniques')->default(0);
            $table->timestamps();

            $table->unique(['domain_id', 'date', 'path']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('snippet_events');
    }
};
