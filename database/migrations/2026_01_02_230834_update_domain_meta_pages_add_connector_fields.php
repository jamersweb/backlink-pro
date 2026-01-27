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
        Schema::table('domain_meta_pages', function (Blueprint $table) {
            if (!Schema::hasColumn('domain_meta_pages', 'remote_id')) {
                $table->string('remote_id')->nullable()->after('external_id');
            }
            
            if (!Schema::hasColumn('domain_meta_pages', 'handle')) {
                $table->string('handle')->nullable()->after('remote_id');
            }
        });

        // Add index separately to avoid issues
        $indexName = 'domain_meta_pages_domain_id_resource_type_index';
        $indexes = DB::select("SHOW INDEXES FROM domain_meta_pages WHERE Key_name = ?", [$indexName]);
        
        if (empty($indexes)) {
            Schema::table('domain_meta_pages', function (Blueprint $table) {
                $table->index(['domain_id', 'resource_type'], 'domain_meta_pages_domain_id_resource_type_index');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('domain_meta_pages', function (Blueprint $table) {
            $table->dropIndex('domain_meta_pages_domain_id_resource_type_index');
        });

        Schema::table('domain_meta_pages', function (Blueprint $table) {
            if (Schema::hasColumn('domain_meta_pages', 'handle')) {
                $table->dropColumn('handle');
            }
            
            if (Schema::hasColumn('domain_meta_pages', 'remote_id')) {
                $table->dropColumn('remote_id');
            }
        });
    }
};
