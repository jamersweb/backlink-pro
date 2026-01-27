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
        Schema::create('partner_applications', function (Blueprint $table) {
            $table->id();
            $table->string('partner_type')->index(); // referral/reseller/integration
            $table->string('name');
            $table->string('email')->index();
            $table->string('company')->nullable();
            $table->string('website')->nullable();
            $table->string('company_size')->nullable();
            $table->string('client_count')->nullable();
            $table->string('regions')->nullable();
            $table->text('message');
            $table->json('utm_json')->nullable();
            $table->string('ip')->nullable();
            $table->string('user_agent', 1000)->nullable();
            $table->string('status')->default('new')->index(); // new/reviewing/approved/rejected
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('partner_applications');
    }
};
