<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('white_label_report_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('domain_id')->nullable()->constrained('domains')->nullOnDelete();
            $table->string('client_name');
            $table->string('client_website', 500);
            $table->string('report_title');
            $table->date('reporting_period_start');
            $table->date('reporting_period_end');
            $table->text('target_keywords')->nullable();
            $table->text('notes')->nullable();
            $table->text('recommendations')->nullable();
            $table->timestamps();

            $table->index(['organization_id', 'user_id']);
            $table->index(['user_id', 'client_website']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('white_label_report_profiles');
    }
};
