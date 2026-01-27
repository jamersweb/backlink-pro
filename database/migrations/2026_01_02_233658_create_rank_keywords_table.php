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
        if (Schema::hasTable('rank_keywords')) {
            $requiredColumns = ['id', 'domain_id', 'keyword', 'target_url', 'location_code', 'language_code', 'device', 'schedule', 'is_active', 'source', 'created_at', 'updated_at'];
            $hasAllColumns = true;
            foreach ($requiredColumns as $column) {
                if (!Schema::hasColumn('rank_keywords', $column)) {
                    $hasAllColumns = false;
                    break;
                }
            }
            if ($hasAllColumns) {
                return;
            }
            
            try {
                $constraints = DB::select("
                    SELECT CONSTRAINT_NAME 
                    FROM information_schema.KEY_COLUMN_USAGE 
                    WHERE TABLE_SCHEMA = DATABASE() 
                    AND TABLE_NAME = 'rank_keywords' 
                    AND REFERENCED_TABLE_NAME IS NOT NULL
                ");
                foreach ($constraints as $constraint) {
                    DB::statement("ALTER TABLE rank_keywords DROP FOREIGN KEY {$constraint->CONSTRAINT_NAME}");
                }
            } catch (\Exception $e) {}
            Schema::dropIfExists('rank_keywords');
        }
        
        Schema::create('rank_keywords', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('domain_id')->index();
            $table->string('keyword')->index();
            $table->text('target_url')->nullable();
            $table->string('location_code')->nullable();
            $table->string('language_code', 10)->nullable();
            $table->enum('device', ['desktop', 'mobile'])->default('desktop');
            $table->enum('schedule', ['daily', 'weekly', 'manual'])->default('weekly');
            $table->boolean('is_active')->default(true)->index();
            $table->enum('source', ['manual', 'keyword_map', 'gsc', 'brief'])->default('manual');
            $table->timestamps();

            $table->unique(['domain_id', 'keyword', 'location_code', 'device']);
        });
        
        // Add foreign key with explicit name
        DB::statement('ALTER TABLE rank_keywords ADD CONSTRAINT rank_keywords_domain_id_foreign FOREIGN KEY (domain_id) REFERENCES domains(id) ON DELETE CASCADE');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rank_keywords');
    }
};
