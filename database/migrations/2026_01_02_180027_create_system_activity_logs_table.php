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
        Schema::create('system_activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('domain_id')->nullable()->constrained('domains')->onDelete('set null');
            $table->enum('feature', ['domains', 'audits', 'google', 'backlinks', 'meta', 'insights', 'plans', 'system'])->index();
            $table->string('action');
            $table->enum('status', ['info', 'success', 'warning', 'error'])->index();
            $table->string('message');
            $table->json('context_json')->nullable();
            $table->timestamps();

            $table->index(['feature', 'created_at']);
            $table->index(['domain_id', 'created_at']);
            $table->index(['user_id', 'created_at']);
            $table->index(['status', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('system_activity_logs');
    }
};
