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
        Schema::table('domain_tasks', function (Blueprint $table) {
            $table->string('planner_group')->nullable()->after('effort')->index();
            $table->json('checklist_json')->nullable()->after('description');
            $table->json('why_json')->nullable()->after('checklist_json');
            $table->unsignedInteger('estimated_minutes')->nullable()->after('effort');
            $table->char('source_signature', 40)->nullable()->after('related_entity')->index();
            $table->timestamp('completed_at')->nullable()->after('due_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('domain_tasks', function (Blueprint $table) {
            $table->dropColumn(['planner_group', 'checklist_json', 'why_json', 'estimated_minutes', 'source_signature', 'completed_at']);
        });
    }
};
