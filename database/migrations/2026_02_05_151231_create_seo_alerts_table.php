<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('seo_alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained('organizations')->onDelete('cascade');
            $table->foreignId('rule_id')->nullable()->constrained('seo_alert_rules')->onDelete('set null');
            $table->enum('severity', ['info', 'warning', 'critical'])->default('warning');
            $table->string('title');
            $table->text('message');
            $table->json('diff')->nullable(); // before/after
            $table->date('related_date')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->index(['organization_id', 'severity']);
            $table->index(['rule_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seo_alerts');
    }
};
