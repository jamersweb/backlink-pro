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
        Schema::create('domain_connectors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('domain_id')->constrained('domains')->onDelete('cascade')->unique();
            $table->enum('type', ['wp', 'shopify', 'generic', 'custom_js'])->index();
            $table->enum('status', ['connected', 'disconnected', 'error'])->default('disconnected')->index();
            $table->text('credentials_json')->nullable(); // Encrypted tokens, keys
            $table->json('settings_json')->nullable(); // shopify_shop, api_version, wp_base_url, resource_map, etc.
            $table->timestamp('last_tested_at')->nullable();
            $table->string('last_error_code')->nullable();
            $table->text('last_error_message')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('domain_connectors');
    }
};
