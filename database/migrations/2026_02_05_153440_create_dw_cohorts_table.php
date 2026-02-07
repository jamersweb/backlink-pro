<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('dw_cohorts')) {
            return;
        }

        Schema::create('dw_cohorts', function (Blueprint $table) {
            $table->id();
            $table->string('cohort_month', 7); // YYYY-MM
            $table->enum('cohort_type', ['activation', 'conversion', 'retention']);
            $table->integer('size')->default(0);
            $table->json('metrics')->nullable(); // d1, d7, d14, d30 retention; upgrade rates
            $table->timestamps();

            $table->unique(['cohort_month', 'cohort_type']);
            $table->index('cohort_month');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dw_cohorts');
    }
};
