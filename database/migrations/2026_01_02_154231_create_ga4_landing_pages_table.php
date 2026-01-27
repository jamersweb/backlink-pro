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
        Schema::create('ga4_landing_pages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('domain_id')->constrained('domains')->onDelete('cascade');
            $table->date('date')->index();
            $table->string('landing_page'); // landingPagePlusQueryString
            $table->unsignedInteger('sessions')->default(0);
            $table->unsignedInteger('total_users')->default(0);
            $table->timestamps();

            $table->index(['domain_id', 'date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ga4_landing_pages');
    }
};
