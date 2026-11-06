<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 30)->unique();
            $table->foreignId('patient_id')->constrained('patients')->restrictOnDelete();
            $table->foreignId('doctor_id')->constrained('doctors')->restrictOnDelete();
            $table->foreignId('specialty_id')->constrained('specialties')->restrictOnDelete();
            $table->foreignId('slot_id')->nullable()->unique()->constrained('appointment_slots')->nullOnDelete();
            $table->dateTime('scheduled_at');
            $table->dateTime('ends_at');
            $table->string('status', 20)->default('PROGRAMADA');
            $table->foreignId('rescheduled_from_id')->nullable()->constrained('appointments')->nullOnDelete();
            $table->timestamp('canceled_at')->nullable();
            $table->string('cancellation_reason', 255)->nullable();
            $table->timestamps();

            $table->index(['patient_id', 'status']);
            $table->index(['doctor_id', 'status']);
            $table->index('scheduled_at');
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE appointments ADD CONSTRAINT appointments_time_check CHECK (scheduled_at < ends_at)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('appointments');
    }
};
