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
        Schema::create('free_plan_requests', function (Blueprint $table) {
            $table->id();
            $table->string('website')->index();
            $table->string('segment')->index();
            $table->string('risk_mode')->index();
            $table->json('goals');
            $table->json('target_pages')->nullable();
            $table->json('competitors')->nullable();
            $table->integer('monthly_budget')->nullable();
            $table->string('email')->nullable()->index();
            $table->json('utm_json')->nullable();
            $table->string('ip')->nullable();
            $table->string('user_agent', 1000)->nullable();
            $table->string('status')->default('new')->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('free_plan_requests');
    }
};
