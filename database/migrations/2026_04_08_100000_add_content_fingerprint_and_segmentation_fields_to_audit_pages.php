<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('audit_pages', function (Blueprint $table) {
            if (!Schema::hasColumn('audit_pages', 'content_fingerprint')) {
                $table->char('content_fingerprint', 64)->nullable()->after('visible_text_length');
                $table->index('content_fingerprint', 'audit_pages_content_fingerprint_idx');
            }
            if (!Schema::hasColumn('audit_pages', 'segment_key')) {
                $table->string('segment_key', 64)->nullable()->after('content_fingerprint');
                $table->index('segment_key', 'audit_pages_segment_key_idx');
            }
            if (!Schema::hasColumn('audit_pages', 'near_duplicate_cluster_id')) {
                $table->string('near_duplicate_cluster_id', 64)->nullable()->after('segment_key');
                $table->index('near_duplicate_cluster_id', 'audit_pages_nd_cluster_idx');
            }
        });
    }

    public function down(): void
    {
        Schema::table('audit_pages', function (Blueprint $table) {
            if (Schema::hasColumn('audit_pages', 'near_duplicate_cluster_id')) {
                $table->dropIndex('audit_pages_nd_cluster_idx');
                $table->dropColumn('near_duplicate_cluster_id');
            }
            if (Schema::hasColumn('audit_pages', 'segment_key')) {
                $table->dropIndex('audit_pages_segment_key_idx');
                $table->dropColumn('segment_key');
            }
            if (Schema::hasColumn('audit_pages', 'content_fingerprint')) {
                $table->dropIndex('audit_pages_content_fingerprint_idx');
                $table->dropColumn('content_fingerprint');
            }
        });
    }
};
