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
        Schema::create('appointment_communication', function (Blueprint $table) {
            $table->foreignId('appointment_id')->constrained()->onUpdate('cascade')->onDelete('cascade');
            $table->enum('communication', ['chat', 'voice_call', 'video_call']);
            $table->foreign('communication')->references('communication')->on('communications');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('appointment_communication');
    }
};
