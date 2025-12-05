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
            $table->unsignedTinyInteger('min_pa')->default(0)->after('daily_backlink_limit');
            $table->unsignedTinyInteger('max_pa')->default(100)->after('min_pa');
            $table->unsignedTinyInteger('min_da')->default(0)->after('max_pa');
            $table->unsignedTinyInteger('max_da')->default(100)->after('min_da');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->dropColumn(['min_pa', 'max_pa', 'min_da', 'max_da']);
        });
    }
};


