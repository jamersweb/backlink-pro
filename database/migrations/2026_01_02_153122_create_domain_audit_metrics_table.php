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
        Schema::create('domain_audit_metrics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('domain_audit_id')->constrained('domain_audits')->onDelete('cascade');
            $table->string('url')->index();
            $table->enum('strategy', ['mobile', 'desktop'])->default('mobile');
            $table->unsignedSmallInteger('performance_score')->nullable(); // 0-100
            $table->unsignedInteger('lcp_ms')->nullable(); // Largest Contentful Paint
            $table->unsignedInteger('cls_x1000')->nullable(); // Cumulative Layout Shift * 1000
            $table->unsignedInteger('inp_ms')->nullable(); // Interaction to Next Paint
            $table->unsignedInteger('fcp_ms')->nullable(); // First Contentful Paint
            $table->unsignedInteger('ttfb_ms')->nullable(); // Time to First Byte
            $table->json('raw_json')->nullable();
            $table->timestamps();

            $table->unique(['domain_audit_id', 'url', 'strategy']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('domain_audit_metrics');
    }
};
