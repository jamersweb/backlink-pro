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
        // Make it NOT NULL again (this might fail if there are NULL values)
        DB::statement('ALTER TABLE `backlinks` MODIFY `keyword` VARCHAR(255) NOT NULL');
    }
};
