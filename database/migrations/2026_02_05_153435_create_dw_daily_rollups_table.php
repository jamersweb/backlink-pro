<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('dw_daily_rollups')) {
            return;
        }

        Schema::create('dw_daily_rollups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->nullable()->constrained('organizations')->onDelete('set null');
            $table->date('date')->index();
            $table->json('metrics'); // audits_created, leads_created, pdf_exports, upgrades, churn, etc.
            $table->timestamps();

            $table->unique(['organization_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dw_daily_rollups');
    }
};
