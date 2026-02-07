<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('seo_alert_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained('organizations')->onDelete('cascade');
            $table->string('name');
            $table->enum('type', ['rank_drop', 'gsc_clicks_drop', 'ga4_sessions_drop', 'conversion_drop']);
            $table->json('config'); // thresholds, lookback_days
            $table->boolean('is_enabled')->default(true);
            $table->json('notify_emails')->nullable();
            $table->timestamps();

            $table->index(['organization_id', 'is_enabled']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seo_alert_rules');
    }
};
