<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('ga4_page_metrics')) {
            return;
        }

        Schema::create('ga4_page_metrics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained('organizations')->onDelete('cascade');
            $table->string('property_id');
            $table->date('date')->index();
            $table->string('page_path');
            $table->string('page_title')->nullable();
            $table->integer('views')->default(0);
            $table->integer('active_users')->default(0);
            $table->integer('conversions')->nullable();
            $table->timestamps();

            $table->index(['organization_id', 'property_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ga4_page_metrics');
    }
};
