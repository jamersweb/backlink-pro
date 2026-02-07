<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('dw_cohort_members')) {
            return;
        }

        Schema::create('dw_cohort_members', function (Blueprint $table) {
            $table->id();
            $table->string('cohort_month', 7); // YYYY-MM
            $table->foreignId('organization_id')->constrained('organizations')->onDelete('cascade');
            $table->enum('cohort_type', ['activation', 'conversion', 'retention']);
            $table->timestamp('joined_at');
            $table->timestamps();

            $table->unique(['cohort_month', 'organization_id', 'cohort_type'], 'dw_cohort_members_unique');
            $table->index(['cohort_month', 'cohort_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dw_cohort_members');
    }
};
