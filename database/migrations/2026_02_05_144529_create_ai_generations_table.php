<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_generations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->nullable()->constrained('organizations')->onDelete('cascade');
            $table->foreignId('audit_id')->nullable()->constrained('audits')->onDelete('cascade');
            $table->foreignId('lead_id')->nullable()->constrained('leads')->onDelete('set null');
            $table->enum('type', [
                'report_summary',
                'fix_plan',
                'snippet_pack',
                'chat_answer',
                'competitor_summary',
            ]);
            $table->string('input_fingerprint', 64)->index();
            $table->string('model_key', 50)->default('llm-v1');
            $table->string('prompt_version', 20)->default('v1.0.0');
            $table->enum('status', ['queued', 'running', 'completed', 'failed'])->default('queued');
            $table->json('input')->nullable();
            $table->json('output')->nullable();
            $table->integer('tokens_in')->nullable();
            $table->integer('tokens_out')->nullable();
            $table->integer('cost_cents')->nullable();
            $table->text('error')->nullable();
            $table->timestamps();

            $table->index(['audit_id', 'type', 'status']);
            $table->index(['input_fingerprint', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_generations');
    }
};
