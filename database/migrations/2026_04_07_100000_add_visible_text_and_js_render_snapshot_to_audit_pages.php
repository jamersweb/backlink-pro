<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('audit_pages', function (Blueprint $table) {
            if (!Schema::hasColumn('audit_pages', 'visible_text_length')) {
                $table->unsignedInteger('visible_text_length')->nullable()->after('word_count');
            }
            if (!Schema::hasColumn('audit_pages', 'js_render_snapshot')) {
                $table->json('js_render_snapshot')->nullable()->after('security_headers');
            }
        });
    }

    public function down(): void
    {
        Schema::table('audit_pages', function (Blueprint $table) {
            $cols = [];
            if (Schema::hasColumn('audit_pages', 'js_render_snapshot')) {
                $cols[] = 'js_render_snapshot';
            }
            if (Schema::hasColumn('audit_pages', 'visible_text_length')) {
                $cols[] = 'visible_text_length';
            }
            if ($cols !== []) {
                $table->dropColumn($cols);
            }
        });
    }
};
