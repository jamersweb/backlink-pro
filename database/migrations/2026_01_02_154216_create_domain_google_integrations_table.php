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
        Schema::create('domain_google_integrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('domain_id')->constrained('domains')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('connected_account_id')->constrained('connected_accounts')->onDelete('cascade');
            $table->string('gsc_property')->nullable(); // e.g. "sc-domain:example.com" OR "https://example.com/"
            $table->string('ga4_property_id')->nullable(); // store "properties/123456789"
            $table->enum('status', ['connected', 'error', 'disconnected'])->default('connected');
            $table->timestamp('last_synced_at')->nullable();
            $table->text('last_sync_error')->nullable();
            $table->timestamps();

            $table->unique('domain_id'); // One integration per domain
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('domain_google_integrations');
    }
};
