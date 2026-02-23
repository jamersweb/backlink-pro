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
        Schema::table('backlinks', function (Blueprint $table) {
            $table->dropForeign(['campaign_id']);
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE `backlinks` MODIFY `campaign_id` BIGINT UNSIGNED NULL');
        }
        // SQLite: skip column change to avoid MySQL-specific syntax; FK only

        Schema::table('backlinks', function (Blueprint $table) {
            $table->foreign('campaign_id')
                ->references('id')
                ->on('campaigns')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('backlinks', function (Blueprint $table) {
            // Drop the foreign key constraint
            $table->dropForeign(['campaign_id']);
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE `backlinks` MODIFY `campaign_id` BIGINT UNSIGNED NOT NULL');
        }

        // Re-add the foreign key constraint
        Schema::table('backlinks', function (Blueprint $table) {
            $table->foreign('campaign_id')
                ->references('id')
                ->on('campaigns')
                ->onDelete('cascade');
        });
    }
};
