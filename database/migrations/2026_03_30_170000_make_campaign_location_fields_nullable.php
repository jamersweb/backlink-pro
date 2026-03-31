<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('campaigns')) {
            return;
        }

        Schema::table('campaigns', function (Blueprint $table) {
            if (Schema::hasColumn('campaigns', 'company_state')) {
                $table->unsignedBigInteger('company_state')->nullable()->change();
            }
            if (Schema::hasColumn('campaigns', 'company_city')) {
                $table->unsignedBigInteger('company_city')->nullable()->change();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('campaigns')) {
            return;
        }

        // Keep rollback safe on existing nullable rows.
        if (DB::table('campaigns')->whereNull('company_state')->orWhereNull('company_city')->exists()) {
            return;
        }

        Schema::table('campaigns', function (Blueprint $table) {
            if (Schema::hasColumn('campaigns', 'company_state')) {
                $table->unsignedBigInteger('company_state')->nullable(false)->change();
            }
            if (Schema::hasColumn('campaigns', 'company_city')) {
                $table->unsignedBigInteger('company_city')->nullable(false)->change();
            }
        });
    }
};
