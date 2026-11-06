<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('patients', function (Blueprint $table) {
            $table->id();
            $table->string('document_number', 10)->unique();
            $table->string('first_name', 100);
            $table->string('last_name', 100);
            $table->date('birth_date');
            $table->string('insurance_type', 30);
            $table->string('address', 255);
            $table->string('primary_phone', 20);
            $table->string('secondary_phone', 20);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['last_name', 'first_name']);
            $table->index('is_active');
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE patients ADD CONSTRAINT patients_document_number_check CHECK (document_number REGEXP '^[0-9]{8}([0-9]{2})?$')");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('patients');
    }
};
