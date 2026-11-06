<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        if (! Schema::hasTable('appointment_slots')) {
            throw new RuntimeException('No existe la tabla appointment_slots para instalar los triggers.');
        }

        $triggers = [
            'trg_appointment_slots_no_overlap_bi' => <<<'SQL'
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
            'trg_appointment_slots_no_overlap_bu' => <<<'SQL'
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

        $created = [];

        try {
            foreach ($triggers as $name => $statement) {
                $exists = DB::selectOne(
                    'SELECT COUNT(*) AS total
                     FROM information_schema.TRIGGERS
                     WHERE TRIGGER_SCHEMA = DATABASE()
                       AND TRIGGER_NAME = ?',
                    [$name],
                );

                if ((int) $exists->total > 0) {
                    continue;
                }

                DB::unprepared($statement);
                $created[] = $name;
            }
        } catch (QueryException $exception) {
            foreach ($created as $name) {
                DB::unprepared("DROP TRIGGER IF EXISTS {$name}");
            }

            throw $exception;
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::unprepared('DROP TRIGGER IF EXISTS trg_appointment_slots_no_overlap_bi');
        DB::unprepared('DROP TRIGGER IF EXISTS trg_appointment_slots_no_overlap_bu');
    }
};
