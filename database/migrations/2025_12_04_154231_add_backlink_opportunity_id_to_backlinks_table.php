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
        Schema::table('backlinks', function (Blueprint $table) {
            $table->foreignId('backlink_opportunity_id')
                ->nullable()
                ->after('campaign_id')
                ->constrained('backlink_opportunities')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('backlinks', function (Blueprint $table) {
            $table->dropForeign(['backlink_opportunity_id']);
            $table->dropColumn('backlink_opportunity_id');
        });
    }
};
