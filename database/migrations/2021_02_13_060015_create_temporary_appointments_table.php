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
        Schema::create('temporary_appointments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->constrained()->onUpdate('cascade')->onDelete('cascade');
            $table->foreignId('doctor_id')->nullable()->constrained()->onUpdate('cascade')->onDelete('cascade');
            $table->unsignedBigInteger('appointment_id')->nullable()->index();

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

            $table->text('patient_notes')->nullable()->comment('Patient Special Notes');

            $table->text('communications')->nullable()->comment('Preferred Communication Method');

            //Payment Information
            $table->decimal('amount',10,2)->default(0.00);
            $table->decimal('discount',10,2)->default(0.00);
            $table->decimal('tax',10,2)->default(0.00);
            $table->decimal('total',10,2)->default(0.00);

            $table->enum('appointment_type', ['regular', 'instant'])->default('regular')->comment('Regular/Instant Appointment');
            $table->text('instant_counseling')->nullable()->comment('Instant Counseling Filter Parameter');

            //Myfatoorah Fields
            $table->string('payment_session_id')->nullable()->comment('Myfatoorah Session ID');
            $table->string('payment_invoice_id')->nullable()->comment('Myfatoorah Invoice ID');
            $table->string('payment_invoice_url')->nullable()->comment('Myfatoorah Invoice URL');
            $table->text('payment_response')->nullable()->comment('Myfatoorah Invoice Payment Response');

            $table->enum('payment_status', ['unpaid', 'paid', 'failed'])->default('unpaid');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('temporary_appointments');
    }
};
