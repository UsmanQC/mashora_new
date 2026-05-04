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
        Schema::create('medications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('appointment_id')->constrained()->onUpdate('cascade')->onDelete('cascade');
            $table->text('name')->nullable();
            $table->string('dosage')->nullable();
            $table->string('usage')->nullable()->comment('Usage');
            $table->string('frequency')->nullable()->comment('Frequency');
            $table->string('duration')->nullable();
            $table->string('duration_measurement')->nullable();
            $table->text('instructions')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('medications');
    }
};
