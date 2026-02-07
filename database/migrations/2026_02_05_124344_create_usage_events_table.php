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
        if (Schema::hasTable('usage_events')) {
            // Table exists, check if we need to add organization_id
            if (!Schema::hasColumn('usage_events', 'organization_id')) {
                Schema::table('usage_events', function (Blueprint $table) {
                    $table->foreignId('organization_id')->nullable()->after('id')->constrained('organizations')->onDelete('cascade');
                });
            }
            return;
        }

        Schema::create('usage_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained('organizations')->onDelete('cascade');
            $table->foreignId('audit_id')->nullable()->constrained('audits')->onDelete('set null');
            $table->enum('event_type', [
                'audit_created',
                'page_crawled',
                'lighthouse_run',
                'pdf_export',
                'csv_export',
                'widget_audit_created',
            ]);
            $table->integer('quantity')->default(1);
            $table->json('metadata')->nullable(); // url, page_url, preset, etc.
            $table->timestamp('occurred_at')->index();
            $table->timestamps();

            // Indexes
            $table->index(['organization_id', 'event_type', 'occurred_at']);
            $table->index(['organization_id', 'occurred_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('usage_events');
    }
};
