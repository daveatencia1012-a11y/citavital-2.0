<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement('ALTER TABLE availabilities ADD CONSTRAINT availabilities_weekday_check CHECK (weekday BETWEEN 1 AND 7)');
        DB::statement('ALTER TABLE availabilities ADD CONSTRAINT availabilities_time_check CHECK (start_time < end_time)');
        DB::statement('ALTER TABLE availabilities ADD CONSTRAINT availabilities_duration_check CHECK (appointment_duration BETWEEN 5 AND 240)');
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement('ALTER TABLE availabilities DROP CHECK availabilities_weekday_check');
        DB::statement('ALTER TABLE availabilities DROP CHECK availabilities_time_check');
        DB::statement('ALTER TABLE availabilities DROP CHECK availabilities_duration_check');
    }
};
