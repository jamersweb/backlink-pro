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
        Schema::create('branding_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->unique()->constrained('organizations')->onDelete('cascade');
            $table->string('brand_name')->nullable();
            $table->string('logo_path')->nullable();
            $table->string('primary_color', 7)->nullable(); // Hex color
            $table->string('secondary_color', 7)->nullable();
            $table->string('accent_color', 7)->nullable();
            $table->text('report_footer_text')->nullable();
            $table->boolean('hide_backlinkpro_branding')->default(false);
            $table->enum('pdf_template', ['default', 'pro'])->default('pro');
            $table->string('email_from_name')->nullable();
            $table->string('email_from_address')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('branding_profiles');
    }
};
