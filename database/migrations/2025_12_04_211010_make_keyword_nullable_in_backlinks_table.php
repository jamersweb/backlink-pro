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
        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE `backlinks` MODIFY `keyword` VARCHAR(255) NULL');
        }
        // SQLite: skip column change for test compatibility
    }

    public function down(): void
    {
        DB::statement("UPDATE backlinks SET keyword = '' WHERE keyword IS NULL");
        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE `backlinks` MODIFY `keyword` VARCHAR(255) NOT NULL');
        }
    }
};
