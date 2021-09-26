<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddLottertyStep extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('lottery_steps', function (Blueprint $table) {
            $table->id();
            $table->string('step_name');
            $table->integer('is_visible');
            $table->integer('order');
            $table->integer('prize_number');
            $table->integer('is_repeat_draw');
            $table->integer('participate_list');
            $table->string('lottery_code');
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
        Schema::dropIfExists('lottery_steps');
    }
}
