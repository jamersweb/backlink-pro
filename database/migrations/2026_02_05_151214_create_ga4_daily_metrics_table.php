<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('ga4_daily_metrics')) {
            return;
        }

        Schema::create('ga4_daily_metrics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained('organizations')->onDelete('cascade');
            $table->string('property_id');
            $table->date('date')->index();
            $table->integer('sessions')->default(0);
            $table->integer('users')->default(0);
            $table->integer('new_users')->default(0);
            $table->decimal('engagement_rate', 6, 4)->nullable();
            $table->integer('avg_engagement_time_sec')->nullable();
            $table->integer('conversions')->nullable();
            $table->decimal('revenue', 12, 2)->nullable();
            $table->timestamps();

            $table->unique(['organization_id', 'property_id', 'date']);
            $table->index(['organization_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ga4_daily_metrics');
    }
};
