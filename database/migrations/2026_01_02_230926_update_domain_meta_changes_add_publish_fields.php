<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('domain_meta_changes', function (Blueprint $table) {
            if (!Schema::hasColumn('domain_meta_changes', 'publish_attempts')) {
                $table->unsignedInteger('publish_attempts')->default(0)->after('error_message');
            }
            
            if (!Schema::hasColumn('domain_meta_changes', 'publish_error_code')) {
                $table->string('publish_error_code')->nullable()->after('publish_attempts');
            }
            
            if (!Schema::hasColumn('domain_meta_changes', 'publish_error_message')) {
                $table->text('publish_error_message')->nullable()->after('publish_error_code');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('domain_meta_changes', function (Blueprint $table) {
            if (Schema::hasColumn('domain_meta_changes', 'publish_attempts')) {
                $table->dropColumn('publish_attempts');
            }
            
            if (Schema::hasColumn('domain_meta_changes', 'publish_error_code')) {
                $table->dropColumn('publish_error_code');
            }
            
            if (Schema::hasColumn('domain_meta_changes', 'publish_error_message')) {
                $table->dropColumn('publish_error_message');
            }
        });
    }
};
