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
        Schema::create('backlinks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')
                  ->constrained('campaigns')
                  ->onDelete('cascade');
            $table->unsignedBigInteger('site_account_id')->nullable();
            $table->string('url'); // Target backlink URL
            $table->enum('type', ['comment', 'profile', 'forum', 'guestposting']);
            $table->string('keyword'); // Anchor keyword
            $table->string('anchor_text')->nullable(); // Actual anchor text used
            $table->enum('status', ['pending', 'submitted', 'verified', 'error'])
                  ->default('pending');
            $table->timestamp('verified_at')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('backlinks');
    }
};

