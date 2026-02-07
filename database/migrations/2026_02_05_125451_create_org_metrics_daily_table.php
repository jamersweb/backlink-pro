<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('org_metrics_daily', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained('organizations')->onDelete('cascade');
            $table->date('date');
            $table->integer('audits_created')->default(0);
            $table->integer('pages_crawled')->default(0);
            $table->integer('lighthouse_runs')->default(0);
            $table->integer('pdf_exports')->default(0);
            $table->integer('leads_generated')->default(0);
            $table->timestamps();

            $table->unique(['organization_id', 'date']);
            $table->index(['organization_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('org_metrics_daily');
    }
};
