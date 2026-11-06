<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('availabilities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('doctor_id')->constrained('doctors')->cascadeOnDelete();
            $table->unsignedTinyInteger('weekday')->comment('ISO 8601: lunes 1, domingo 7.');
            $table->time('start_time');
            $table->time('end_time');
            $table->unsignedSmallInteger('appointment_duration')->comment('Duración en minutos.');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['doctor_id', 'weekday', 'start_time', 'end_time']);
            $table->index(['doctor_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('availabilities');
    }
};
