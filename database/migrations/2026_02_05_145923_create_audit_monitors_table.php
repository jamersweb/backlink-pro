<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_monitors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained('organizations')->onDelete('cascade');
            $table->string('name');
            $table->string('target_url', 2048);
            $table->string('schedule_rrule'); // e.g. "FREQ=WEEKLY;BYDAY=MO"
            $table->integer('pages_limit')->default(25);
            $table->integer('crawl_depth')->default(2);
            $table->integer('lighthouse_pages')->default(1);
            $table->boolean('is_enabled')->default(true);
            $table->json('notify_emails')->nullable();
            $table->text('slack_webhook_url_encrypted')->nullable();
            $table->timestamps();

            $table->index(['organization_id', 'is_enabled']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_monitors');
    }
};
