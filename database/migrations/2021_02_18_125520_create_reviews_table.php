<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateReviewsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('doctor_id')->constrained()->onUpdate('cascade')->onDelete('cascade')->comment('Doctor ID');
            $table->foreignId('user_id')->constrained()->onUpdate('cascade')->onDelete('cascade')->comment('Patient ID');
            $table->foreignId('appointment_id')->constrained()->comment('Appointment ID');
            $table->string('user_name')->nullable();
            $table->tinyInteger('rating')->default(0)->comment('Average review');
            $table->tinyInteger('commitment')->default(0)->comment('How was the specialist’s commitment to attendance?');
            $table->tinyInteger('interaction')->default(0)->comment('How was the specialist’s interaction and empathy?');
            $table->tinyInteger('behavior')->default(0)->comment('How was the specialist’s behavior?');
            $table->tinyInteger('environment')->default(0)->comment('Was the environment appropriate and quiet?');
            $table->tinyInteger('listening')->default(0)->comment('Was the specialist a good listener?');
            $table->text('comment')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('reviews');
    }
}
