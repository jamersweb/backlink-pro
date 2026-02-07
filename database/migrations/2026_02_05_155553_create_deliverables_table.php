<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('deliverables', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('clients')->onDelete('cascade');
            $table->foreignId('client_project_id')->nullable()->constrained('client_projects')->onDelete('set null');
            $table->string('month', 7); // YYYY-MM
            $table->enum('type', ['audit', 'report', 'fix', 'backlinks', 'rank_setup', 'content']);
            $table->string('title');
            $table->text('description')->nullable();
            $table->date('due_date');
            $table->enum('status', ['planned', 'in_progress', 'awaiting_approval', 'delivered', 'overdue', 'canceled'])->default('planned');
            $table->foreignId('assigned_to_user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->json('evidence')->nullable(); // links, pdf ids, PR urls
            $table->timestamps();

            $table->index(['client_id', 'month', 'status']);
            $table->index(['client_id', 'due_date']);
            $table->index(['assigned_to_user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deliverables');
    }
};
