<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('client_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained('client_conversations')->onDelete('cascade');
            $table->enum('sender_type', ['agency', 'client']);
            $table->foreignId('sender_user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->text('body');
            $table->json('attachments')->nullable(); // stored file paths
            $table->timestamps();

            $table->index(['conversation_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('client_messages');
    }
};
