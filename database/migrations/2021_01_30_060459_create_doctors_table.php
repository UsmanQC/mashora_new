<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDoctorsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('doctors', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('name_ar')->nullable();
            $table->string('email')->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('phone')->index()->nullable();
            $table->string('verification_code')->nullable();
            $table->string('password')->nullable();
            $table->text('profile_photo_path')->nullable();
            $table->enum('gender', ['male', 'female', 'other'])->nullable();
            $table->text('profile_detail_path')->nullable();
            $table->string('signature')->nullable();
            $table->string('registration_number')->nullable();
            $table->integer('degree_id')->nullable();
            $table->integer('speciality_id')->nullable();
            $table->tinyInteger('experience')->nullable()->comment("Experience in years");
            $table->string('medical_career_level')->nullable();
            $table->text('about')->nullable();
            $table->text('about_ar')->nullable();
            $table->enum("spoken_languages", ['ar', 'en', 'ar_en'])->nullable();
            $table->boolean('is_online')->default(true);
            $table->boolean('profile_completed')->default(false);
            $table->boolean('accept_instant_appointment')->default(true);
            $table->tinyInteger('commission')->default(30)->comment('Mashora Commission');
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->boolean('active_status')->default(0)->comment('Online Status');
            $table->rememberToken();
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
        Schema::dropIfExists('doctors');
    }
}
