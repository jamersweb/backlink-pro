<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('audit_id')->constrained('audits')->onDelete('cascade');
            $table->json('snapshot'); // scores + key counts
            $table->timestamps();

            $table->index('audit_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_snapshots');
    }
};
