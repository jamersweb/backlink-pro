<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dw_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->nullable()->constrained('organizations')->onDelete('set null');
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('anonymous_id')->nullable();
            $table->string('event_name')->index();
            $table->timestamp('event_time')->index();
            $table->json('properties')->nullable();
            $table->json('context')->nullable(); // utm, referrer, device, geo
            $table->enum('source', ['app', 'widget', 'email', 'api'])->default('app');
            $table->timestamps();

            $table->index(['organization_id', 'event_time']);
            $table->index(['user_id', 'event_time']);
            $table->index(['anonymous_id', 'event_time']);
            $table->index(['event_name', 'event_time']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dw_events');
    }
};
