<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('appointment_slots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('doctor_id')->constrained('doctors')->cascadeOnDelete();
            $table->dateTime('starts_at');
            $table->dateTime('ends_at');
            $table->string('status', 20)->default('DISPONIBLE');
            $table->timestamps();

            $table->unique(['doctor_id', 'starts_at', 'ends_at']);
            $table->index(['doctor_id', 'starts_at']);
            $table->index('status');
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE appointment_slots ADD CONSTRAINT appointment_slots_time_check CHECK (starts_at < ends_at)');

            $this->createOverlapTriggers();
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::unprepared('DROP TRIGGER IF EXISTS trg_appointment_slots_no_overlap_bi');
            DB::unprepared('DROP TRIGGER IF EXISTS trg_appointment_slots_no_overlap_bu');
        }

        Schema::dropIfExists('appointment_slots');
    }

    /**
     * Refuerzo adicional a nivel de motor para inserciones directas.
     *
     * MySQL exige el privilegio SUPER cuando el binary log está habilitado.
     * Si el usuario no lo tiene, la validación transaccional del servicio sigue
     * activa y la migración no debe fallar.
     */
    private function createOverlapTriggers(): void
    {
        $triggers = [
            <<<'SQL'
                CREATE TRIGGER trg_appointment_slots_no_overlap_bi
                BEFORE INSERT ON appointment_slots
                FOR EACH ROW
                BEGIN
                    IF EXISTS (
                        SELECT 1
                        FROM appointment_slots
                        WHERE doctor_id = NEW.doctor_id
                          AND NEW.starts_at < ends_at
                          AND NEW.ends_at > starts_at
                    ) THEN
                        SIGNAL SQLSTATE '45000'
                            SET MESSAGE_TEXT = 'El turno se superpone con otro turno del mismo medico.';
                    END IF;
                END
            SQL,
            <<<'SQL'
                CREATE TRIGGER trg_appointment_slots_no_overlap_bu
                BEFORE UPDATE ON appointment_slots
                FOR EACH ROW
                BEGIN
                    IF EXISTS (
                        SELECT 1
                        FROM appointment_slots
                        WHERE doctor_id = NEW.doctor_id
                          AND id <> NEW.id
                          AND NEW.starts_at < ends_at
                          AND NEW.ends_at > starts_at
                    ) THEN
                        SIGNAL SQLSTATE '45000'
                            SET MESSAGE_TEXT = 'El turno se superpone con otro turno del mismo medico.';
                    END IF;
                END
            SQL,
        ];

        foreach ($triggers as $trigger) {
            try {
                DB::unprepared($trigger);
            } catch (QueryException $exception) {
                if (! str_contains($exception->getMessage(), 'SUPER privilege')) {
                    throw $exception;
                }

                Log::warning(
                    'No se pudo crear el trigger de solapamiento de turnos. '.
                    'La validación transaccional del servicio sigue activa.',
                    ['error' => $exception->getMessage()],
                );
            }
        }
    }
};
