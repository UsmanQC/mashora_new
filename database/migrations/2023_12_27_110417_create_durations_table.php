<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('durations', function (Blueprint $table) {
            $table->smallInteger('duration');
            $table->string('title')->nullable();
            $table->string('title_ar')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->primary('duration');
        });

        Schema::create('doctor_duration', function (Blueprint $table) {
            $table->foreignId('doctor_id')->constrained()->onUpdate('cascade')->onDelete('cascade')->comment('Doctor ID');
            $table->smallInteger('duration');
            $table->foreign('duration')->references('duration')->on('durations');
            $table->double('price', 10, 2)->default(0.00);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('durations');
        Schema::dropIfExists('doctor_duration');
    }
};
