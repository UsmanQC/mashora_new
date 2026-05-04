<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSpecialitiesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('specialities', function (Blueprint $table) {
            $table->id();
            // $table->decimal('price')->default('0.00');
            // $table->smallInteger('slot_period')->unsigned()->default(0);
            $table->string('title')->nullable();
            $table->string('title_ar')->nullable();
            // $table->text('description')->nullable();
            // $table->text('description_ar')->nullable();
            // $table->text('image_path')->nullable();
            // $table->string('backgroundColor')->nullable();
            // $table->integer('orderShow')->nullable();
            // $table->string('icon_path')->nullable();
            // $table->text('imageSvg')->nullable();
            // $table->string('colorClass')->nullable();
            // $table->integer('total_time_call')->nullable();
            // $table->integer('warning_time_call')->nullable();
            // $table->integer('alert_time_call')->nullable();
            $table->boolean('status')->default(1);
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
        Schema::drop('specialities');
    }
}
