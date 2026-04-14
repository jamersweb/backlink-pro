<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('ga4_source_metrics')) {
            return;
        }

        Schema::create('ga4_source_metrics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained('organizations')->onDelete('cascade');
            $table->string('property_id');
            $table->date('date')->index();
            $table->string('source_medium');
            $table->unsignedInteger('sessions')->default(0);
            $table->unsignedInteger('active_users')->default(0);
            $table->timestamps();

            $table->unique(['organization_id', 'property_id', 'date', 'source_medium'], 'ga4_source_unique');
            $table->index(['organization_id', 'property_id', 'date'], 'ga4_source_org_property_date_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ga4_source_metrics');
    }
};

