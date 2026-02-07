<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('backlink_outreach_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('prospect_id')->constrained('backlink_prospects')->onDelete('cascade');
            $table->enum('channel', ['email', 'contact_form', 'linkedin', 'other'])->default('email');
            $table->string('subject');
            $table->text('body');
            $table->enum('status', ['draft', 'sent', 'replied'])->default('draft');
            $table->timestamp('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('backlink_outreach_messages');
    }
};
