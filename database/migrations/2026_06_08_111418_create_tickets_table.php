<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tickets', function (Blueprint $table): void {
            $table->id();
            $table->string('ticket_number')->unique();
            $table->morphs('creator');
            $table->foreignId('ticket_category_id')->constrained()->cascadeOnDelete();
            $table->string('subject');
            $table->text('message');
            $table->string('status', 20)->default('open');
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};
