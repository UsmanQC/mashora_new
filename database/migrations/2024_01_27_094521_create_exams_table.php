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
        Schema::create('exams', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique()->nullable();
            $table->text('title')->nullable()->comment('English Option');
            $table->text('title_ar')->nullable()->comment('Arabic Option');
            $table->smallInteger('estimate_time')->nullable()->comment('Estimate Time in Minutes');
            $table->text('image_path')->nullable()->comment('Image Path');
            $table->boolean('enabled')->default(1);
            $table->unsignedInteger('order')->default(0);
            $table->string('metric_result_type')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('questions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique()->nullable();
            $table->foreignId('exam_id')->constrained()->cascadeOnDelete()->cascadeOnUpdate();
            $table->text('title')->nullable()->comment('English Question');
            $table->text('title_ar')->nullable()->comment('Arabic Question');
            $table->unsignedInteger('order')->default(0);
            $table->timestamps();
			$table->softDeletes();
        });

        Schema::create('options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('question_id')->constrained()->cascadeOnDelete()->cascadeOnUpdate();
            $table->text('title')->nullable()->comment('English Option');
            $table->text('title_ar')->nullable()->comment('Arabic Option');
            $table->tinyInteger('value')->default(0);
            $table->unsignedInteger('order')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('options');
        Schema::dropIfExists('questions');
        Schema::dropIfExists('exams');
    }
};
