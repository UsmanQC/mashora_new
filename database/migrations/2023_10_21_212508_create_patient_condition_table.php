<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePatientConditionTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('patient_conditions', function (Blueprint $table) {
            $table->id();
            $table->string("title_ar")->nullable();
            $table->string("title_en")->nullable();
            $table->string("path_blue_grey")->nullable(); 
            $table->string("path_blue")->nullable(); 
            $table->string("path_grey")->nullable(); 
            $table->string("path_white")->nullable(); 
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
        Schema::dropIfExists('patient_conditions');
    }
}
