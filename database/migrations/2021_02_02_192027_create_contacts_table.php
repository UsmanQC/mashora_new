<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateContactsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('contacts', function (Blueprint $table) {
            $table->id();            
            $table->string('reason')->nullable();
            $table->string('name')->nullable();
            $table->text('message')->nullable();
            $table->tinyInteger('app')->nullable()->comment('1:Doctor App 2:Patient App');
            $table->tinyInteger('is_read')->default(0);
            $table->string('user_type')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
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
        Schema::drop('contacts');
    }
}
