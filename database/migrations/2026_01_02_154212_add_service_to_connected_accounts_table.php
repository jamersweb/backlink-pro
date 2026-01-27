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
        Schema::table('connected_accounts', function (Blueprint $table) {
            $table->string('service')->default('gmail')->after('provider')->index();
        });

        // Update existing rows to have service='gmail' (they're all Gmail accounts)
        \DB::table('connected_accounts')->update(['service' => 'gmail']);

        // Add unique constraint
        Schema::table('connected_accounts', function (Blueprint $table) {
            $table->unique(['user_id', 'provider', 'email', 'service'], 'connected_accounts_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('connected_accounts', function (Blueprint $table) {
            $table->dropUnique('connected_accounts_unique');
            $table->dropColumn('service');
        });
    }
};
