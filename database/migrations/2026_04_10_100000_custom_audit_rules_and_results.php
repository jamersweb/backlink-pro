<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            if (!Schema::hasColumn('organizations', 'custom_source_search_rules')) {
                $table->json('custom_source_search_rules')->nullable()->after('spelling_allowlist');
            }
            if (!Schema::hasColumn('organizations', 'custom_extraction_rules')) {
                $table->json('custom_extraction_rules')->nullable()->after('custom_source_search_rules');
            }
        });

        Schema::table('audits', function (Blueprint $table) {
            if (!Schema::hasColumn('audits', 'custom_source_search_rules')) {
                $table->json('custom_source_search_rules')->nullable()->after('spelling_allowlist');
            }
            if (!Schema::hasColumn('audits', 'custom_extraction_rules')) {
                $table->json('custom_extraction_rules')->nullable()->after('custom_source_search_rules');
            }
        });

        Schema::table('audit_pages', function (Blueprint $table) {
            if (!Schema::hasColumn('audit_pages', 'response_headers_json')) {
                $table->json('response_headers_json')->nullable()->after('html_size_bytes');
            }
        });

        if (!Schema::hasTable('audit_custom_search_results')) {
            Schema::create('audit_custom_search_results', function (Blueprint $table) {
                $table->id();
                $table->foreignId('audit_id')->constrained('audits')->cascadeOnDelete();
                $table->foreignId('audit_page_id')->constrained('audit_pages')->cascadeOnDelete();
                $table->string('url', 2048);
                $table->string('rule_key', 64);
                $table->string('rule_name', 255)->nullable();
                $table->string('target_scope', 32);
                $table->string('match_type', 32);
                $table->string('pattern_preview', 512)->nullable();
                $table->boolean('expect_match')->default(true);
                $table->boolean('matched')->default(false);
                $table->unsignedInteger('match_count')->default(0);
                $table->text('sample_match')->nullable();
                $table->string('severity', 16)->default('warning');
                $table->text('error_message')->nullable();
                $table->string('segment_key', 64)->nullable();
                $table->timestamps();

                $table->index(['audit_id', 'rule_key'], 'audit_custom_search_audit_rule_idx');
                $table->index(['audit_id', 'matched'], 'audit_custom_search_audit_matched_idx');
            });
        }

        if (!Schema::hasTable('audit_custom_extraction_results')) {
            Schema::create('audit_custom_extraction_results', function (Blueprint $table) {
                $table->id();
                $table->foreignId('audit_id')->constrained('audits')->cascadeOnDelete();
                $table->foreignId('audit_page_id')->constrained('audit_pages')->cascadeOnDelete();
                $table->string('url', 2048);
                $table->string('rule_key', 64);
                $table->string('rule_name', 255)->nullable();
                $table->string('target_scope', 32);
                $table->string('extraction_type', 32);
                $table->string('extractor', 1024);
                $table->string('attribute', 128)->nullable();
                $table->boolean('multiple')->default(false);
                $table->json('values')->nullable();
                $table->boolean('missing')->default(false);
                $table->text('error_message')->nullable();
                $table->string('segment_key', 64)->nullable();
                $table->string('fingerprint', 64)->nullable();
                $table->timestamps();

                $table->index(['audit_id', 'rule_key'], 'audit_custom_ext_audit_rule_idx');
                $table->index(['audit_id', 'fingerprint'], 'audit_custom_ext_fp_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_custom_extraction_results');
        Schema::dropIfExists('audit_custom_search_results');

        Schema::table('audit_pages', function (Blueprint $table) {
            if (Schema::hasColumn('audit_pages', 'response_headers_json')) {
                $table->dropColumn('response_headers_json');
            }
        });

        Schema::table('audits', function (Blueprint $table) {
            foreach (['custom_extraction_rules', 'custom_source_search_rules'] as $col) {
                if (Schema::hasColumn('audits', $col)) {
                    $table->dropColumn($col);
                }
            }
        });

        Schema::table('organizations', function (Blueprint $table) {
            foreach (['custom_extraction_rules', 'custom_source_search_rules'] as $col) {
                if (Schema::hasColumn('organizations', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
