<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sla_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('clients')->onDelete('cascade');
            $table->foreignId('deliverable_id')->nullable()->constrained('deliverables')->onDelete('set null');
            $table->enum('type', ['audit_delivery_late', 'report_late', 'response_late']);
            $table->enum('severity', ['warning', 'critical'])->default('warning');
            $table->text('message');
            $table->timestamp('occurred_at');
            $table->timestamps();

            $table->index(['client_id', 'occurred_at']);
            $table->index(['deliverable_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sla_events');
    }
};
