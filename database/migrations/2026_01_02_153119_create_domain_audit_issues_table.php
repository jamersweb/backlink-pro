<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('domain_audit_issues', function (Blueprint $table) {
            $table->id();
            $table->foreignId('domain_audit_id')->constrained('domain_audits')->onDelete('cascade');
            $table->foreignId('domain_audit_page_id')->constrained('domain_audit_pages')->onDelete('cascade');
            $table->enum('severity', ['critical', 'warning', 'info']);
            $table->string('type')->index();
            $table->string('message');
            $table->json('data_json')->nullable();
            $table->timestamps();

            $table->index(['domain_audit_id', 'severity']);
            $table->index(['domain_audit_id', 'type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('domain_audit_issues');
    }
};
