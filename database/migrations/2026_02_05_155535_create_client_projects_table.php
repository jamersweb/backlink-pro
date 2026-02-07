<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('client_projects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('clients')->onDelete('cascade');
            $table->string('name');
            $table->string('target_url', 2048);
            $table->string('country_code', 2)->default('US');
            $table->string('language_code', 5)->default('en');
            $table->boolean('monitoring_enabled')->default(true);
            $table->timestamps();

            $table->index(['client_id', 'monitoring_enabled']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('client_projects');
    }
};
