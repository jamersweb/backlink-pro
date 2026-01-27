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
        Schema::create('public_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('domain_id')->constrained('domains')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->char('token', 64)->unique()->index();
            $table->string('title')->nullable();
            $table->enum('status', ['active', 'revoked', 'expired'])->default('active')->index();
            $table->timestamp('expires_at')->nullable();
            $table->string('password_hash')->nullable();
            $table->json('settings_json');
            $table->json('snapshot_json')->nullable();
            $table->timestamp('snapshot_generated_at')->nullable();
            $table->timestamps();

            $table->index(['domain_id', 'created_at']);
            $table->index(['user_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('public_reports');
    }
};
