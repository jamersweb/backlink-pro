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
        // Plans table already exists, add Stripe fields if they don't exist
        Schema::table('plans', function (Blueprint $table) {
            if (!Schema::hasColumn('plans', 'stripe_price_id_monthly')) {
                $table->string('stripe_price_id_monthly')->nullable()->after('price_annual');
            }
            if (!Schema::hasColumn('plans', 'stripe_price_id_yearly')) {
                $table->string('stripe_price_id_yearly')->nullable()->after('stripe_price_id_monthly');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->dropColumn(['stripe_price_id_monthly', 'stripe_price_id_yearly']);
        });
    }
};
