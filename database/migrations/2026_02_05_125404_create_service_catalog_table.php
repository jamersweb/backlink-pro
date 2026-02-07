<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_catalog', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->integer('base_price_cents');
            $table->string('currency', 10)->default('usd');
            $table->enum('pricing_model', ['fixed', 'per_page', 'tiered', 'custom_quote'])->default('fixed');
            $table->json('includes')->nullable();
            $table->integer('estimated_days')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_catalog');
    }
};
