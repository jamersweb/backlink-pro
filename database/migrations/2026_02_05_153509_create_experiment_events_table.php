<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('experiment_events')) {
            return;
        }

        Schema::create('experiment_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('experiment_id')->constrained('experiments')->onDelete('cascade');
            $table->foreignId('variant_id')->constrained('experiment_variants')->onDelete('cascade');
            $table->string('event_name'); // conversion event
            $table->string('subject_id'); // org/user/anon ID
            $table->timestamp('occurred_at')->index();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['experiment_id', 'variant_id', 'event_name']);
            $table->index(['experiment_id', 'occurred_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('experiment_events');
    }
};
