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
        Schema::create('page_speed_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->nullable()->constrained()->nullOnDelete();
            $table->string('url', 2048);
            $table->string('strategy', 20);
            $table->timestamp('fetched_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->string('status', 20)->default('success');
            $table->unsignedSmallInteger('http_status')->nullable();
            $table->string('error_message', 1024)->nullable();
            $table->json('payload')->nullable();
            $table->json('kpis')->nullable();
            $table->timestamps();

            $table->index(['organization_id', 'strategy']);
            $table->index(['expires_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('page_speed_results');
    }
};
