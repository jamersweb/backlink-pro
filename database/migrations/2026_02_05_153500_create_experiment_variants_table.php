<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('experiment_variants')) {
            return;
        }

        Schema::create('experiment_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('experiment_id')->constrained('experiments')->onDelete('cascade');
            $table->string('key'); // A/B/C
            $table->integer('weight')->default(50);
            $table->json('config')->nullable(); // copy, UI flags
            $table->timestamps();

            $table->unique(['experiment_id', 'key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('experiment_variants');
    }
};
