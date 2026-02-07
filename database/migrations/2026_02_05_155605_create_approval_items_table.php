<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('approval_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('approval_id')->constrained('approvals')->onDelete('cascade');
            $table->string('item_type'); // file_diff, link_prospect, message, task
            $table->json('item_payload');
            $table->timestamps();

            $table->index('approval_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('approval_items');
    }
};
