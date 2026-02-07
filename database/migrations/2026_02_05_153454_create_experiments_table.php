<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('experiments')) {
            return;
        }

        Schema::create('experiments', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('name');
            $table->enum('status', ['draft', 'running', 'paused', 'completed'])->default('draft');
            $table->integer('traffic_allocation')->default(100); // 0-100
            $table->timestamps();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('experiments');
    }
};
