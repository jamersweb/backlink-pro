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
        Schema::table('domain_backlinks', function (Blueprint $table) {
            if (!Schema::hasColumn('domain_backlinks', 'ref_domain_id')) {
                $table->unsignedBigInteger('ref_domain_id')->nullable()->after('source_domain')->index();
            }
            
            if (!Schema::hasColumn('domain_backlinks', 'quality_score')) {
                $table->unsignedSmallInteger('quality_score')->default(0)->after('risk_flags_json');
            }
            
            if (!Schema::hasColumn('domain_backlinks', 'risk_score')) {
                $table->unsignedSmallInteger('risk_score')->default(0)->after('quality_score');
            }
            
            if (!Schema::hasColumn('domain_backlinks', 'flags_json')) {
                $table->json('flags_json')->nullable()->after('risk_score');
            }
            
            if (!Schema::hasColumn('domain_backlinks', 'action_status')) {
                $table->enum('action_status', ['keep', 'review', 'remove', 'disavow'])->default('keep')
                    ->after('flags_json')->index();
            }
        });

        // Add foreign key constraint separately if column was just added
        if (!Schema::hasColumn('domain_backlinks', 'ref_domain_id')) {
            // Column doesn't exist, it was added above, now add foreign key
            try {
                $constraints = DB::select("
                    SELECT CONSTRAINT_NAME 
                    FROM information_schema.KEY_COLUMN_USAGE 
                    WHERE TABLE_SCHEMA = DATABASE() 
                    AND TABLE_NAME = 'domain_backlinks' 
                    AND COLUMN_NAME = 'ref_domain_id' 
                    AND REFERENCED_TABLE_NAME IS NOT NULL
                ");
                
                if (empty($constraints)) {
                    DB::statement('ALTER TABLE domain_backlinks ADD CONSTRAINT domain_backlinks_ref_domain_id_foreign FOREIGN KEY (ref_domain_id) REFERENCES backlink_ref_domains(id) ON DELETE SET NULL');
                }
            } catch (\Exception $e) {
                // Ignore if constraint already exists or table doesn't exist
            }
        } else {
            // Column exists, check if foreign key exists
            try {
                $constraints = DB::select("
                    SELECT CONSTRAINT_NAME 
                    FROM information_schema.KEY_COLUMN_USAGE 
                    WHERE TABLE_SCHEMA = DATABASE() 
                    AND TABLE_NAME = 'domain_backlinks' 
                    AND COLUMN_NAME = 'ref_domain_id' 
                    AND REFERENCED_TABLE_NAME IS NOT NULL
                ");
                
                if (empty($constraints)) {
                    DB::statement('ALTER TABLE domain_backlinks ADD CONSTRAINT domain_backlinks_ref_domain_id_foreign FOREIGN KEY (ref_domain_id) REFERENCES backlink_ref_domains(id) ON DELETE SET NULL');
                }
            } catch (\Exception $e) {
                // Ignore if constraint already exists
            }
        }

        // Add indexes
        Schema::table('domain_backlinks', function (Blueprint $table) {
            if (!$this->hasIndex('domain_backlinks', 'domain_backlinks_run_action_status_index')) {
                $table->index(['run_id', 'action_status'], 'domain_backlinks_run_action_status_index');
            }
            if (!$this->hasIndex('domain_backlinks', 'domain_backlinks_run_risk_score_index')) {
                $table->index(['run_id', 'risk_score'], 'domain_backlinks_run_risk_score_index');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('domain_backlinks', function (Blueprint $table) {
            $table->dropIndex('domain_backlinks_run_action_status_index');
            $table->dropIndex('domain_backlinks_run_risk_score_index');
            
            if (Schema::hasColumn('domain_backlinks', 'action_status')) {
                $table->dropColumn('action_status');
            }
            if (Schema::hasColumn('domain_backlinks', 'flags_json')) {
                $table->dropColumn('flags_json');
            }
            if (Schema::hasColumn('domain_backlinks', 'risk_score')) {
                $table->dropColumn('risk_score');
            }
            if (Schema::hasColumn('domain_backlinks', 'quality_score')) {
                $table->dropColumn('quality_score');
            }
            if (Schema::hasColumn('domain_backlinks', 'ref_domain_id')) {
                try {
                    DB::statement('ALTER TABLE domain_backlinks DROP FOREIGN KEY domain_backlinks_ref_domain_id_foreign');
                } catch (\Exception $e) {
                    // Ignore if constraint doesn't exist
                }
                $table->dropColumn('ref_domain_id');
            }
        });
    }

    protected function hasIndex($table, $indexName): bool
    {
        try {
            $indexes = DB::select("SHOW INDEXES FROM {$table} WHERE Key_name = ?", [$indexName]);
            return !empty($indexes);
        } catch (\Exception $e) {
            return false;
        }
    }
};
