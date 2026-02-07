<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('monthly_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained('organizations')->onDelete('cascade');
            $table->string('month', 7); // YYYY-MM
            $table->string('file_path');
            $table->string('download_token', 64)->unique();
            $table->timestamp('generated_at');
            $table->timestamps();

            $table->unique(['organization_id', 'month']);
            $table->index('download_token');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('monthly_reports');
    }
};
