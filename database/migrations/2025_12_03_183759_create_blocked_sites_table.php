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
        Schema::create('blocked_sites', function (Blueprint $table) {
            $table->id();
            $table->string('domain'); // e.g., 'example.com' or 'subdomain.example.com'
            $table->text('reason')->nullable(); // Why it was blocked (opt-out, complaint, etc.)
            $table->string('blocked_by')->nullable(); // Admin email or system
            $table->boolean('is_active')->default(true); // Can be temporarily disabled
            $table->timestamps();
            
            $table->index('domain');
            $table->index('is_active');
            $table->unique('domain');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('blocked_sites');
    }
};
