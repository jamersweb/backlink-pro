<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_packages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained('organizations')->onDelete('cascade');
            $table->string('name');
            $table->text('description')->nullable();
            $table->integer('monthly_price_cents')->default(0);
            $table->string('currency', 10)->default('usd');
            $table->json('included_deliverables')->nullable(); // audits_per_month, reports_per_month, etc.
            $table->json('sla_days')->nullable(); // audit_delivery, issue_response, report_delivery
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['organization_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_packages');
    }
};
