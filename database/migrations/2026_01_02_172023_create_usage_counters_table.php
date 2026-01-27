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
        Schema::create('usage_counters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->enum('period_type', ['day', 'month'])->index();
            $table->string('period_key')->index(); // "2026-01-02" for day, "2026-01" for month
            $table->string('metric_key')->index();
            $table->unsignedBigInteger('used')->default(0);
            $table->timestamps();

            $table->unique(['user_id', 'period_type', 'period_key', 'metric_key']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('usage_counters');
    }
};
