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
        Schema::table('audits', function (Blueprint $table) {
            $table->foreignId('organization_id')->nullable()->after('user_id')->constrained('organizations')->onDelete('cascade');
            $table->boolean('is_gated')->default(true)->after('is_public');
            $table->json('public_summary')->nullable()->after('is_gated');
            $table->foreignId('lead_id')->nullable()->after('public_summary')->constrained('leads')->onDelete('set null');
            $table->json('plan_snapshot')->nullable()->after('lead_id');

            // Indexes
            $table->index('organization_id');
            $table->index('lead_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('audits', function (Blueprint $table) {
            $table->dropForeign(['organization_id']);
            $table->dropForeign(['lead_id']);
            $table->dropColumn([
                'organization_id',
                'is_gated',
                'public_summary',
                'lead_id',
                'plan_snapshot',
            ]);
        });
    }
};
