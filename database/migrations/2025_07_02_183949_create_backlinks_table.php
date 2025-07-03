<?php

// database/migrations/xxxx_xx_xx_xxxxxx_create_backlinks_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBacklinksTable extends Migration
{
    public function up()
    {
        Schema::create('backlinks', function (Blueprint $table) {
            $table->bigIncrements('id');                           // Primary key
            $table->foreignId('campaign_id')                        // FK to campaigns
                  ->constrained('campaigns')
                  ->onDelete('cascade');
            $table->string('url');                                  // Target backlink URL
            $table->enum('type', ['comment','profile','forum','guestposting']);
                                                                    // Type of backlink
            $table->string('keyword');                              // Anchor keyword
            $table->enum('status', ['pending','success','error'])
                  ->default('pending');                             // Processing status
            $table->timestamps();                                   // created_at & updated_at
        });
    }

    public function down()
    {
        Schema::dropIfExists('backlinks');
    }
}

