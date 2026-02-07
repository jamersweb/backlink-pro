<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_request_id')->constrained('service_requests')->onDelete('cascade');
            $table->enum('sender_type', ['user', 'admin', 'provider']);
            $table->unsignedBigInteger('sender_id')->nullable();
            $table->text('message');
            $table->json('attachments')->nullable();
            $table->timestamp('created_at');

            $table->index(['service_request_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_messages');
    }
};
