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
        Schema::create('domain_alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('domain_id')->constrained('domains')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('type')->index();
            $table->enum('severity', ['critical', 'warning', 'info'])->index();
            $table->string('title');
            $table->text('message')->nullable();
            $table->text('related_url')->nullable();
            $table->json('related_entity')->nullable();
            $table->boolean('is_read')->default(false)->index();
            $table->timestamps();

            $table->index(['domain_id', 'created_at']);
            $table->index(['domain_id', 'is_read']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('domain_alerts');
    }
};
