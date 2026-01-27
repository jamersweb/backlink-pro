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
        if (Schema::hasTable('rank_results')) {
            $requiredColumns = ['id', 'rank_check_id', 'domain_id', 'rank_keyword_id', 'keyword', 'position', 'found_url', 'matched', 'serp_top_urls_json', 'features_json', 'fetched_at', 'created_at', 'updated_at'];
            $hasAllColumns = true;
            foreach ($requiredColumns as $column) {
                if (!Schema::hasColumn('rank_results', $column)) {
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
                    AND TABLE_NAME = 'rank_results' 
                    AND REFERENCED_TABLE_NAME IS NOT NULL
                ");
                foreach ($constraints as $constraint) {
                    DB::statement("ALTER TABLE rank_results DROP FOREIGN KEY {$constraint->CONSTRAINT_NAME}");
                }
            } catch (\Exception $e) {}
            Schema::dropIfExists('rank_results');
        }
        
        Schema::create('rank_results', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('rank_check_id')->index();
            $table->unsignedBigInteger('domain_id')->index();
            $table->unsignedBigInteger('rank_keyword_id')->index();
            $table->string('keyword')->index();
            $table->unsignedSmallInteger('position')->nullable(); // 1..100, null if not found
            $table->text('found_url')->nullable();
            $table->boolean('matched')->default(false);
            $table->json('serp_top_urls_json')->nullable();
            $table->json('features_json')->nullable();
            $table->timestamp('fetched_at');
            $table->timestamps();

            $table->index(['domain_id', 'rank_keyword_id', 'fetched_at']);
            $table->index(['domain_id', 'keyword', 'fetched_at']);
        });
        
        // Add foreign keys with explicit names
        DB::statement('ALTER TABLE rank_results ADD CONSTRAINT rank_results_rank_check_id_foreign FOREIGN KEY (rank_check_id) REFERENCES rank_checks(id) ON DELETE CASCADE');
        DB::statement('ALTER TABLE rank_results ADD CONSTRAINT rank_results_domain_id_foreign FOREIGN KEY (domain_id) REFERENCES domains(id) ON DELETE CASCADE');
        DB::statement('ALTER TABLE rank_results ADD CONSTRAINT rank_results_rank_keyword_id_foreign FOREIGN KEY (rank_keyword_id) REFERENCES rank_keywords(id) ON DELETE CASCADE');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rank_results');
    }
};
