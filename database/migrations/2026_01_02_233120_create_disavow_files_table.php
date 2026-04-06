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
        // Check if tables already exist
        $filesExists = Schema::hasTable('disavow_files');
        $entriesExists = Schema::hasTable('disavow_entries');
        
        if ($filesExists && $entriesExists) {
            $requiredFilesColumns = ['id', 'domain_id', 'user_id', 'version', 'status', 'notes', 'file_text', 'generated_at', 'created_at', 'updated_at'];
            $requiredEntriesColumns = ['id', 'disavow_file_id', 'entry_type', 'value', 'value_hash', 'reason', 'created_at', 'updated_at'];
            
            $hasAllFiles = true;
            foreach ($requiredFilesColumns as $column) {
                if (!Schema::hasColumn('disavow_files', $column)) {
                    $hasAllFiles = false;
                    break;
                }
            }
            
            $hasAllEntries = true;
            foreach ($requiredEntriesColumns as $column) {
                if (!Schema::hasColumn('disavow_entries', $column)) {
                    $hasAllEntries = false;
                    break;
                }
            }
            
            if ($hasAllFiles && $hasAllEntries) {
                return; // Skip migration
            }
        }
        
        // Drop existing tables if incomplete
        if ($entriesExists) {
            try {
                $constraints = DB::select("
                    SELECT CONSTRAINT_NAME 
                    FROM information_schema.KEY_COLUMN_USAGE 
                    WHERE TABLE_SCHEMA = DATABASE() 
                    AND TABLE_NAME = 'disavow_entries' 
                    AND REFERENCED_TABLE_NAME IS NOT NULL
                ");
                foreach ($constraints as $constraint) {
                    DB::statement("ALTER TABLE disavow_entries DROP FOREIGN KEY {$constraint->CONSTRAINT_NAME}");
                }
            } catch (\Exception $e) {}
            Schema::dropIfExists('disavow_entries');
        }
        
        if ($filesExists) {
            try {
                $constraints = DB::select("
                    SELECT CONSTRAINT_NAME 
                    FROM information_schema.KEY_COLUMN_USAGE 
                    WHERE TABLE_SCHEMA = DATABASE() 
                    AND TABLE_NAME = 'disavow_files' 
                    AND REFERENCED_TABLE_NAME IS NOT NULL
                ");
                foreach ($constraints as $constraint) {
                    DB::statement("ALTER TABLE disavow_files DROP FOREIGN KEY {$constraint->CONSTRAINT_NAME}");
                }
            } catch (\Exception $e) {}
            Schema::dropIfExists('disavow_files');
        }
        
        Schema::create('disavow_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('domain_id')->constrained('domains')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedInteger('version');
            $table->enum('status', ['draft', 'exported', 'archived'])->default('draft')->index();
            $table->text('notes')->nullable();
            $table->longText('file_text');
            $table->timestamp('generated_at');
            $table->timestamps();

            $table->unique(['domain_id', 'version']);
        });

        Schema::create('disavow_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('disavow_file_id')->constrained('disavow_files')->cascadeOnDelete();
            $table->enum('entry_type', ['domain', 'url'])->index();
            $table->text('value'); // "example.com" or full URL
            $table->char('value_hash', 64)->index();
            $table->string('reason')->nullable();
            $table->timestamps();

            $table->unique(['disavow_file_id', 'entry_type', 'value_hash']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('disavow_entries');
        Schema::dropIfExists('disavow_files');
    }
};
