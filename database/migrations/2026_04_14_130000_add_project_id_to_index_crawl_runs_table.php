<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('index_crawl_runs', function (Blueprint $table) {
            if (!Schema::hasColumn('index_crawl_runs', 'project_id')) {
                $table->foreignId('project_id')->nullable()->after('domain_id')->constrained('projects')->nullOnDelete();
                $table->index(['project_id', 'created_at']);
            }
        });
    }

    public function down(): void
    {
        Schema::table('index_crawl_runs', function (Blueprint $table) {
            if (Schema::hasColumn('index_crawl_runs', 'project_id')) {
                $table->dropConstrainedForeignId('project_id');
            }
        });
    }
};
