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
        // Make keyword column nullable
        DB::statement('ALTER TABLE `backlinks` MODIFY `keyword` VARCHAR(255) NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // First, update any NULL values to empty string
        DB::statement("UPDATE `backlinks` SET `keyword` = '' WHERE `keyword` IS NULL");
        
        // Then make it NOT NULL
        DB::statement('ALTER TABLE `backlinks` MODIFY `keyword` VARCHAR(255) NOT NULL');
    }
};
