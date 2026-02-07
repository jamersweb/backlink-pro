<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('ml_anomalies')) {
            return;
        }

        Schema::create('ml_anomalies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained('organizations')->onDelete('cascade');
            $table->string('metric_key'); // gsc_clicks, ga4_sessions, conversions, avg_rank
            $table->date('date')->index();
            $table->decimal('actual_value', 15, 4);
            $table->decimal('expected_value', 15, 4);
            $table->decimal('anomaly_score', 5, 4); // 0-1
            $table->enum('severity', ['info', 'warning', 'critical'])->default('warning');
            $table->json('explanation')->nullable();
            $table->timestamps();

            $table->index(['organization_id', 'metric_key', 'date']);
            $table->index(['organization_id', 'severity']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ml_anomalies');
    }
};
