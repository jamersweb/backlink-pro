<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_request_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_request_id')->constrained('service_requests')->onDelete('cascade');
            $table->foreignId('service_catalog_id')->constrained('service_catalog')->onDelete('cascade');
            $table->integer('quantity')->default(1);
            $table->integer('unit_price_cents');
            $table->json('meta')->nullable(); // affected_count, pages, etc.
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_request_items');
    }
};
