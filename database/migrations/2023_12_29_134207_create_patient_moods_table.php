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
        // Schema::create('reasons', function (Blueprint $table) {
        //     $table->id();
        //     $table->string("title_ar")->nullable();
        //     $table->string("title_en")->nullable();
        //     $table->timestamps();
        // });

        Schema::create('patient_moods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onUpdate('cascade')->onDelete('cascade')->comment('Patient ID');
            $table->string('mood')->nullable();
            $table->text('comments')->nullable();
            $table->date("date")->nullable();
            $table->boolean("is_shared")->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        // Schema::create('patient_mood_reason', function (Blueprint $table) {
        //     $table->foreignId('patient_mood_id')->constrained()->comment('Patient Mood ID');
        //     $table->foreignId('reason')->constrained()->comment('Reason ID');
        // });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reasons');
        Schema::dropIfExists('patient_moods');
    }
};
