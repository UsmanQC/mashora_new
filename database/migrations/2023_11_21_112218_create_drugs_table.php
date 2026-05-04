<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDrugsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('drugs', function (Blueprint $table) {
            $table->id();
            $table->text('scientific_name')->nullable()->comment('Medicine Name');
            $table->string('trade_name')->nullable()->comment('Trade Name');
            $table->string('strength')->nullable()->comment('Strength');
            $table->string('strength_unit')->nullable()->comment('Strength Unit');
            $table->string('pharmaceutical_form')->nullable()->comment('Pharmaceutical Form');
            $table->string('administration_route')->nullable()->comment('Usage');
            $table->boolean('flag')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        //For removing existing drug_lists table
        Schema::dropIfExists('drug_lists');

        Schema::create('drug_strengths', function (Blueprint $table) {
            $table->id();
            $table->foreignId('drug_id')->constrained()->onUpdate('cascade')->onDelete('cascade');
            $table->string('dosage')->nullable()->comment('Dosage');
            // $table->string('strength')->nullable();
            // $table->string('strength_unit')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('drugs');
        Schema::dropIfExists('drug_strengths');
    }
}
