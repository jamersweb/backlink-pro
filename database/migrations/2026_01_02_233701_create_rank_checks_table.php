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
        if (Schema::hasTable('rank_checks')) {
            $requiredColumns = ['id', 'domain_id', 'user_id', 'provider_code', 'status', 'keywords_count', 'started_at', 'finished_at', 'error_code', 'error_message', 'created_at', 'updated_at'];
            $hasAllColumns = true;
            foreach ($requiredColumns as $column) {
                if (!Schema::hasColumn('rank_checks', $column)) {
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
                    AND TABLE_NAME = 'rank_checks' 
                    AND REFERENCED_TABLE_NAME IS NOT NULL
                ");
                foreach ($constraints as $constraint) {
                    DB::statement("ALTER TABLE rank_checks DROP FOREIGN KEY {$constraint->CONSTRAINT_NAME}");
                }
            } catch (\Exception $e) {}
            Schema::dropIfExists('rank_checks');
        }
        
        Schema::create('rank_checks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('domain_id')->index();
            $table->unsignedBigInteger('user_id')->index();
            $table->string('provider_code');
            $table->enum('status', ['queued', 'running', 'completed', 'failed'])->default('queued')->index();
            $table->unsignedInteger('keywords_count')->default(0);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->string('error_code')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->index(['domain_id', 'created_at']);
        });
        
        // Add foreign keys with explicit names
        DB::statement('ALTER TABLE rank_checks ADD CONSTRAINT rank_checks_domain_id_foreign FOREIGN KEY (domain_id) REFERENCES domains(id) ON DELETE CASCADE');
        DB::statement('ALTER TABLE rank_checks ADD CONSTRAINT rank_checks_user_id_foreign FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rank_checks');
    }
};
