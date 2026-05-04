<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAppointmentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            $table->string('appointment_number')->nullable();
            $table->foreignId('doctor_id')->nullable()->constrained()->comment('Doctor ID');
            $table->foreignId('user_id')->constrained()->comment('Patient ID');

            //Appointment Session
            $table->dateTime('scheduled_at')->nullable()->comment('Appointment Date Time');
            $table->date('appointment_date')->nullable()->comment('Appointment Date');
            $table->time('start_time')->nullable()->comment('Appointment Start Time');
            $table->time('end_time')->nullable()->comment('Appointment End Time');
            $table->smallInteger('duration')->default(15)->comment('Duration in minutes');

            //Patient Info
            $table->enum('appointment_for', ['self', 'another'])->default('self');
            $table->string('patient_name')->nullable()->comment('Patient Full Name');
            $table->string('patient_email')->nullable()->comment('Patient Email');
            $table->string('patient_phone')->nullable()->comment('Patient Phone');
            // $table->date('patient_birth_date')->nullable();
            // $table->string('patient_gender')->nullable();

            $table->text('patient_notes')->nullable()->comment('Patient Special Notes');
            $table->text('doctor_notes')->nullable()->comment('Doctor Special Notes Only seed doctor');

            //Payment Information
            $table->decimal('amount',10,2)->default(0.00);
            $table->decimal('discount',10,2)->default(0.00);
            $table->decimal('tax',10,2)->default(0.00);
            $table->decimal('total',10,2)->default(0.00);

            $table->enum('appointment_type', ['regular', 'instant'])->default('regular')->comment('Regular/Instant Appointment');
            $table->text('instant_counseling')->nullable()->comment('Instant Counseling Filter Parameter');

            $table->foreignId('invoice_id')->nullable()->constrained()->onUpdate('cascade')->onDelete('cascade');

            //Myfatoorah Fields
            $table->string('payment_session_id')->nullable()->comment('Myfatoorah Session ID');
            $table->string('payment_invoice_id')->nullable()->comment('Myfatoorah Invoice ID');
            $table->string('payment_invoice_url')->nullable()->comment('Myfatoorah Invoice URL');

            //Myfatoorah refund
            $table->string('refund_payment_invoice_id')->nullable()->comment('Myfatoorah Refund Invoice ID');
            $table->text('refund_payment_response')->nullable()->comment('Myfatoorah Refund Invoice Payment Response');

            $table->enum('status', ['new', 'in_process', 'rescheduled', 'cancelled', 'completed','not_attended'])->default('new');
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
        Schema::drop('appointments');
    }
}
