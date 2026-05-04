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
        Schema::create('working_days', function (Blueprint $table) {
            $table->id();
            $table->foreignId('doctor_id');
            $table->enum('day_of_week', ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'])->nullable();
            $table->date('override_date')->nullable();
            $table->boolean('is_working')->default(true);
            $table->timestamps();
			$table->softDeletes();
        });

        Schema::create('working_hours', function (Blueprint $table) {
            $table->id();
            $table->foreignId('working_day_id')->constrained()->onUpdate('cascade')->onDelete('cascade');
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('working_days');
        Schema::dropIfExists('working_hours');
    }
};
