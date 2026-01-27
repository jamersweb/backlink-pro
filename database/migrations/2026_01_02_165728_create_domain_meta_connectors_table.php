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
        Schema::create('domain_meta_connectors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('domain_id')->constrained('domains')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->enum('type', ['wordpress', 'shopify', 'custom_js'])->index();
            $table->enum('status', ['connected', 'error', 'disconnected'])->default('disconnected');
            $table->string('base_url')->nullable();
            $table->text('auth_json')->nullable(); // Encrypted
            $table->timestamp('last_tested_at')->nullable();
            $table->text('last_error')->nullable();
            $table->timestamps();

            $table->unique('domain_id'); // One active connector per domain
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('domain_meta_connectors');
    }
};
