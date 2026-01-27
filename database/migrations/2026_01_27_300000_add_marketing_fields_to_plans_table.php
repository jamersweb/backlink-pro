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
        Schema::table('plans', function (Blueprint $table) {
            // Marketing display fields
            if (!Schema::hasColumn('plans', 'tagline')) {
                $table->string('tagline')->nullable()->after('name');
            }
            if (!Schema::hasColumn('plans', 'price_annual')) {
                $table->unsignedInteger('price_annual')->nullable()->after('price_monthly'); // in cents
            }
            if (!Schema::hasColumn('plans', 'is_highlighted')) {
                $table->boolean('is_highlighted')->default(false)->after('is_active');
            }
            if (!Schema::hasColumn('plans', 'badge')) {
                $table->string('badge')->nullable()->after('is_highlighted'); // e.g., "Most popular"
            }
            if (!Schema::hasColumn('plans', 'sort_order')) {
                $table->unsignedInteger('sort_order')->default(0)->after('badge');
            }
            if (!Schema::hasColumn('plans', 'cta_primary_label')) {
                $table->string('cta_primary_label')->nullable()->after('sort_order');
            }
            if (!Schema::hasColumn('plans', 'cta_primary_href')) {
                $table->string('cta_primary_href')->nullable()->after('cta_primary_label');
            }
            if (!Schema::hasColumn('plans', 'cta_secondary_label')) {
                $table->string('cta_secondary_label')->nullable()->after('cta_primary_href');
            }
            if (!Schema::hasColumn('plans', 'cta_secondary_href')) {
                $table->string('cta_secondary_href')->nullable()->after('cta_secondary_label');
            }
            if (!Schema::hasColumn('plans', 'display_limits')) {
                $table->json('display_limits')->nullable()->after('limits_json'); // For marketing display
            }
            if (!Schema::hasColumn('plans', 'includes')) {
                $table->json('includes')->nullable()->after('features_json'); // Feature bullet points for marketing
            }
            if (!Schema::hasColumn('plans', 'is_public')) {
                $table->boolean('is_public')->default(true)->after('is_active'); // Show on pricing page
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $columns = [
                'tagline', 'price_annual', 'is_highlighted', 'badge', 'sort_order',
                'cta_primary_label', 'cta_primary_href', 'cta_secondary_label', 'cta_secondary_href',
                'display_limits', 'includes', 'is_public'
            ];
            
            foreach ($columns as $column) {
                if (Schema::hasColumn('plans', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
