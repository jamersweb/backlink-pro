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
            $table->integer('pa')->nullable()->after('anchor_text')->comment('Page Authority');
            $table->integer('da')->nullable()->after('pa')->comment('Domain Authority');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('backlinks', function (Blueprint $table) {
            $table->dropColumn(['pa', 'da']);
        });
    }
};
