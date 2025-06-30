<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserGmailsTable extends Migration  // ← changed: add the “s”
{
    public function up()
    {
        Schema::create('user_gmail', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                  ->constrained()
                  ->onDelete('cascade');
            $table->foreignId('company_id')
                  ->constrained('company_information')
                  ->onDelete('cascade');
            $table->foreignId('web_id')
                  ->constrained('web_information')
                  ->onDelete('cascade');
            $table->string('gmail');
            $table->text('password');  
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('user_gmail');
    }
}
