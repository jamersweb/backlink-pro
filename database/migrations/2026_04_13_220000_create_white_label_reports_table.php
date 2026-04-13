<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('white_label_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('white_label_report_profile_id')->constrained()->cascadeOnDelete();
            $table->foreignId('domain_id')->nullable()->constrained()->nullOnDelete();
            $table->string('client_name');
            $table->string('client_website', 500);
            $table->string('report_title');
            $table->date('reporting_period_start')->nullable();
            $table->date('reporting_period_end')->nullable();
            $table->string('status', 40)->default('ready');
            $table->timestamp('generated_at')->nullable();
            $table->json('snapshot_json');
            $table->timestamps();

            $table->index(['organization_id', 'user_id']);
            $table->index(['white_label_report_profile_id', 'generated_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('white_label_reports');
    }
};
