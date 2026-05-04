<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRatingsTable extends Migration
{
    public function up()
    {
        Schema::create('ratings', function (Blueprint $table) {
            $table->bigIncrements('id');


            $table->integer('value');

            $table->unsignedBigInteger('doctor_id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('appointment_id');
            $table->unsignedBigInteger('rating_question_id');

            $table->foreign('doctor_id')->references('id')->on('doctors');
            $table->foreign('appointment_id')->references('id')->on('appointments');
            $table->foreign('rating_question_id')->references('id')->on('rating_questions');
            $table->foreign('user_id')->references('id')->on('users');

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down()
    {
        Schema::dropIfExists('ratings');
    }
}
