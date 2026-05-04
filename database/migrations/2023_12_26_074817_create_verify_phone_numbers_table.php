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
        Schema::create('verify_phone_numbers', function (Blueprint $table) {
            $table->id();
            $table->string('phone')->nullable();
            $table->string('verification_code')->nullable();
            $table->enum('user_type', ['doctor', 'patient'])->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('verify_phone_numbers');
    }
};
