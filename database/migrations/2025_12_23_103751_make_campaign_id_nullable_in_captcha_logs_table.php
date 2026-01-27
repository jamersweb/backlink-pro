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
        Schema::table('captcha_logs', function (Blueprint $table) {
            // Drop the existing foreign key constraint
            $table->dropForeign(['campaign_id']);
            
            // Make campaign_id nullable
            $table->unsignedBigInteger('campaign_id')->nullable()->change();
            
            // Re-add the foreign key constraint with nullable
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
        Schema::table('captcha_logs', function (Blueprint $table) {
            // Drop the foreign key constraint
            $table->dropForeign(['campaign_id']);
            
            // Make campaign_id non-nullable again
            $table->unsignedBigInteger('campaign_id')->nullable(false)->change();
            
            // Re-add the foreign key constraint
            $table->foreign('campaign_id')
                  ->references('id')
                  ->on('campaigns')
                  ->onDelete('cascade');
        });
    }
};
