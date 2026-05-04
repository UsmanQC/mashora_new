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
        Schema::create('communications', function (Blueprint $table) {
            $table->enum('communication', ['chat', 'voice_call', 'video_call']);
            $table->string('title')->nullable();
            $table->string('title_ar')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->primary('communication');
        });

        Schema::create('doctor_communication', function (Blueprint $table) {
            $table->foreignId('doctor_id')->constrained()->onUpdate('cascade')->onDelete('cascade')->comment('Doctor ID');
            $table->enum('communication', ['chat', 'voice_call', 'video_call']);
            $table->foreign('communication')->references('communication')->on('communications');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('communications');
        Schema::dropIfExists('doctor_communication');
    }
};
