<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_sequence_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sequence_id')->constrained('email_sequences')->onDelete('cascade');
            $table->integer('step_order');
            $table->integer('delay_minutes')->default(0);
            $table->string('subject');
            $table->string('template_key'); // blade/mjml key
            $table->json('conditions')->nullable(); // e.g., "has_completed_audit": true
            $table->timestamps();

            $table->index(['sequence_id', 'step_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_sequence_steps');
    }
};
