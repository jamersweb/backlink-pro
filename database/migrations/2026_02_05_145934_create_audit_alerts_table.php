<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('monitor_id')->constrained('audit_monitors')->onDelete('cascade');
            $table->foreignId('audit_id')->constrained('audits')->onDelete('cascade');
            $table->enum('severity', ['info', 'warning', 'critical'])->default('warning');
            $table->string('title');
            $table->text('message');
            $table->json('diff')->nullable(); // what changed
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->index(['monitor_id', 'severity']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_alerts');
    }
};
