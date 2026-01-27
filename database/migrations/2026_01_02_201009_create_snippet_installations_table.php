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
        Schema::create('snippet_installations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('domain_id')->unique()->constrained('domains')->onDelete('cascade');
            $table->char('key', 40)->unique()->index();
            $table->enum('status', ['unknown', 'verified', 'error'])->default('unknown');
            $table->timestamp('first_seen_at')->nullable();
            $table->timestamp('last_seen_at')->nullable()->index();
            $table->string('last_origin_host')->nullable();
            $table->string('agent_version')->nullable();
            $table->json('settings_json');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('snippet_installations');
    }
};
