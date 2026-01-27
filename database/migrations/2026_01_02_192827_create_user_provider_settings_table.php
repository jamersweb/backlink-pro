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
        if (Schema::hasTable('user_provider_settings')) {
            // Check if table has all required columns - if so, skip migration
            $requiredColumns = ['id', 'user_id', 'provider_code', 'settings_json', 'is_enabled', 'created_at', 'updated_at'];
            $hasAllColumns = true;
            foreach ($requiredColumns as $column) {
                if (!Schema::hasColumn('user_provider_settings', $column)) {
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
                    AND TABLE_NAME = 'user_provider_settings' 
                    AND COLUMN_NAME = 'user_id' 
                    AND REFERENCED_TABLE_NAME IS NOT NULL
                ");
                
                foreach ($constraints as $constraint) {
                    // Drop existing foreign key constraint if it exists
                    DB::statement("ALTER TABLE user_provider_settings DROP FOREIGN KEY {$constraint->CONSTRAINT_NAME}");
                }
            } catch (\Exception $e) {
                // Ignore errors if constraint doesn't exist
            }
            
            // Drop and recreate to avoid constraint conflicts
            Schema::dropIfExists('user_provider_settings');
        }
        
        // Table doesn't exist or was dropped, create it
        Schema::create('user_provider_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade')->index();
            $table->string('provider_code')->index();
            $table->text('settings_json')->nullable(); // encrypted
            $table->boolean('is_enabled')->default(true);
            $table->timestamps();

            $table->unique(['user_id', 'provider_code']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_provider_settings');
    }
};
