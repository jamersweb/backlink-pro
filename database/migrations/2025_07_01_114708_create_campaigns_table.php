<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
   public function up()
{
    Schema::create('campaigns', function (Blueprint $table) {
        $table->id();
        $table->foreignId('user_id')->constrained()->onDelete('cascade');
        
        // Web fields
        $table->string('web_name');
        $table->string('web_url');
        $table->string('web_keyword');
        $table->text('web_about');
        $table->enum('web_target', ['worldwide','specific_country']);
        $table->string('country_name')->nullable();
        
        // Company fields
        $table->string('company_name');
        $table->string('company_logo');
        $table->string('company_email_address');
        $table->text('company_address');
        $table->string('company_number');
        $table->foreignId('company_country')->constrained('countries');
        $table->foreignId('company_state')->constrained('states');
        $table->foreignId('company_city')->constrained('cities');
        
        // Gmail fields
        $table->string('gmail');
        $table->string('password');
         $table->enum('status', ['active','inactive'])
              ->default('inactive');
        
        $table->timestamps();
    });
}


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('campaigns');
    }
};
