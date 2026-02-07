<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('competitor_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained('organizations')->onDelete('cascade');
            $table->foreignId('audit_id')->constrained('audits')->onDelete('cascade');
            $table->json('keywords');
            $table->string('country')->nullable();
            $table->enum('status', ['queued', 'running', 'completed', 'failed'])->default('queued');
            $table->timestamps();

            $table->index(['organization_id', 'status']);
            $table->index(['audit_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('competitor_runs');
    }
};
