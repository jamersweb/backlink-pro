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
        Schema::create('marketing_contact_requests', function (Blueprint $table) {
            $table->id();
            $table->string('inquiry_type')->index(); // sales/support/partnership
            $table->string('name');
            $table->string('email')->index();
            $table->string('company')->nullable();
            $table->string('website')->nullable();
            $table->string('segment')->nullable()->index(); // saas/ecommerce/local/agency
            $table->string('budget')->nullable();
            $table->string('preferred_contact')->nullable(); // email/call/whatsapp
            $table->text('message');
            $table->json('utm_json')->nullable();
            $table->string('ip')->nullable();
            $table->string('user_agent', 1000)->nullable();
            $table->string('status')->default('new')->index(); // new/contacted/qualified/closed
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('marketing_contact_requests');
    }
};
