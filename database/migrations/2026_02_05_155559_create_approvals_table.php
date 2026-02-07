<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained('organizations')->onDelete('cascade');
            $table->foreignId('client_id')->constrained('clients')->onDelete('cascade');
            $table->foreignId('client_project_id')->nullable()->constrained('client_projects')->onDelete('set null');
            $table->string('subject_type'); // audit_patch, service_request, outreach_message, deliverable
            $table->unsignedBigInteger('subject_id');
            $table->string('title');
            $table->text('summary')->nullable();
            $table->foreignId('requested_by_user_id')->constrained('users')->onDelete('cascade');
            $table->enum('status', ['pending', 'approved', 'rejected', 'changes_requested'])->default('pending');
            $table->foreignId('decision_by_user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->text('decision_note')->nullable();
            $table->timestamp('requested_at');
            $table->timestamp('decided_at')->nullable();
            $table->timestamps();

            $table->index(['organization_id', 'client_id', 'status']);
            $table->index(['subject_type', 'subject_id']);
            $table->index(['status', 'requested_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('approvals');
    }
};
