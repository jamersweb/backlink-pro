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
        Schema::create('domain_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('domain_id')->constrained('domains')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->enum('source', ['analyzer', 'gsc', 'ga4', 'backlinks', 'meta', 'insights'])->index();
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('priority', ['p1', 'p2', 'p3'])->index();
            $table->unsignedSmallInteger('impact_score')->default(0);
            $table->enum('effort', ['low', 'medium', 'high'])->default('medium');
            $table->enum('status', ['open', 'doing', 'done', 'dismissed'])->default('open')->index();
            $table->timestamp('due_at')->nullable();
            $table->text('related_url')->nullable();
            $table->json('related_entity')->nullable();
            $table->enum('created_by', ['system', 'user'])->default('system');
            $table->timestamps();

            $table->index(['domain_id', 'status', 'priority']);
            $table->index(['domain_id', 'impact_score']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('domain_tasks');
    }
};
