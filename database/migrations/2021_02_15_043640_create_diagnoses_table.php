<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDiagnosesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('diagnoses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('appointment_id')->constrained()->onUpdate('cascade')->onDelete('cascade')->comment('Appointment ID');
            $table->enum('marital_status', ['married', 'unmarried'])->nullable();
            $table->string('diagnosis_name')->nullable()->comment('Diagnosis Name');
            $table->text('medical_history')->nullable();
            $table->text('doctor_notes')->nullable();
            $table->text('treatment_plan')->nullable();
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
        Schema::drop('diagnoses');
    }
}
