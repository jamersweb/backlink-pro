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
        Schema::create('domain_kpi_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('domain_id')->constrained('domains')->onDelete('cascade');
            $table->date('date')->index();
            $table->unsignedSmallInteger('seo_health_score')->nullable();
            $table->unsignedInteger('gsc_clicks_28d')->nullable();
            $table->unsignedInteger('gsc_impressions_28d')->nullable();
            $table->unsignedInteger('ga_sessions_28d')->nullable();
            $table->unsignedInteger('backlinks_new')->nullable();
            $table->unsignedInteger('backlinks_lost')->nullable();
            $table->unsignedInteger('meta_failed_count')->nullable();
            $table->timestamps();

            $table->unique(['domain_id', 'date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('domain_kpi_snapshots');
    }
};
