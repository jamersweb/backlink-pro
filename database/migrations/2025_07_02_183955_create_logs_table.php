<?php
// database/migrations/xxxx_xx_xx_xxxxxx_create_logs_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLogsTable extends Migration
{
    public function up()
    {
        Schema::create('logs', function (Blueprint $table) {
            $table->bigIncrements('id');                           // Primary key
            $table->foreignId('backlink_id')                        // FK to backlinks
                  ->constrained('backlinks')
                  ->onDelete('cascade');
            $table->enum('status', ['pending','success','error']);  // Log entry status
            $table->text('error_message')->nullable();              // Error details, if any
            $table->timestamp('timestamp')->useCurrent();           // When this log entry was created
        });
    }

    public function down()
    {
        Schema::dropIfExists('logs');
    }
}
