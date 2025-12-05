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
        Schema::create('backlink_opportunities', function (Blueprint $table) {
            $table->id();
            $table->string('url')->unique();
            $table->unsignedTinyInteger('pa')->nullable()->comment('Page Authority 0-100');
            $table->unsignedTinyInteger('da')->nullable()->comment('Domain Authority 0-100');
            $table->enum('site_type', ['comment', 'profile', 'forum', 'guestposting', 'other'])->default('comment');
            $table->enum('status', ['active', 'inactive', 'banned'])->default('active');
            $table->unsignedInteger('daily_site_limit')->nullable()->comment('Max links per day from this site');
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('backlink_opportunities');
    }
};


