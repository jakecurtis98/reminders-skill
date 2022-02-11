<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateReminderConfirmationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('reminder_confirmations', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('user_id');
            $table->integer('reminder_id');
            $table->boolean('confirmed')->default(false);
            $table->boolean('force_confirmed')->default(false);
            $table->timestamp('reminder_time');
            $table->timestamp('confirmed_time')->nullable();
            $table->string('options')->default("{}");
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('reminder_confirmations');
    }
}
