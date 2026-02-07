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
            // Drop all foreign key constraints first
            try {
                $dbName = DB::connection()->getDatabaseName();
                $constraints = DB::select("
                    SELECT CONSTRAINT_NAME 
                    FROM information_schema.KEY_COLUMN_USAGE 
                    WHERE TABLE_SCHEMA = ? 
                    AND TABLE_NAME = 'user_provider_settings' 
                    AND REFERENCED_TABLE_NAME IS NOT NULL
                ", [$dbName]);
                
                foreach ($constraints as $constraint) {
                    $constraintName = $constraint->CONSTRAINT_NAME;
                    try {
                        DB::statement("ALTER TABLE user_provider_settings DROP FOREIGN KEY `{$constraintName}`");
                    } catch (\Exception $e) {
                        // Try without backticks if that fails
                        try {
                            DB::statement("ALTER TABLE user_provider_settings DROP FOREIGN KEY {$constraintName}");
                        } catch (\Exception $e2) {
                            // Ignore if constraint doesn't exist
                        }
                    }
                }
            } catch (\Exception $e) {
                // Ignore errors, will try to drop table anyway
            }
            
            // Also try to drop any indexes that might conflict
            try {
                DB::statement("ALTER TABLE user_provider_settings DROP INDEX user_provider_settings_user_id_provider_code_unique");
            } catch (\Exception $e) {
                // Ignore if index doesn't exist
            }
            
            // Drop and recreate to avoid constraint conflicts
            Schema::dropIfExists('user_provider_settings');
        }
        
        // Table doesn't exist or was dropped, create it
        Schema::create('user_provider_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('provider_code')->index();
            $table->text('settings_json')->nullable(); // encrypted
            $table->boolean('is_enabled')->default(true);
            $table->timestamps();

            // Add foreign key with explicit name to avoid conflicts
            $table->foreign('user_id', 'user_provider_settings_user_id_foreign')
                  ->references('id')
                  ->on('users')
                  ->onDelete('cascade');
            
            $table->index('user_id');
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
