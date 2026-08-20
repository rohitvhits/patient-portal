<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * `service_name` (and its siblings) is a comma-joined list of every service resolved for
     * an appointment — for some real ERP records that's dozens of service names, well past the
     * 255-char VARCHAR these started as (`create_appointments_table`), which threw
     * "Data too long for column 'service_name'" on upsert. Widened to TEXT. Raw SQL because
     * doctrine/dbal (needed for Blueprint::change()) isn't installed in this project.
     */
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            return;
        }

        DB::statement('ALTER TABLE appointments MODIFY service_name TEXT NULL');
        DB::statement('ALTER TABLE appointments MODIFY location_name TEXT NULL');
        DB::statement('ALTER TABLE appointments MODIFY doctor_name TEXT NULL');
        DB::statement('ALTER TABLE appointments MODIFY agency_name TEXT NULL');
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            return;
        }

        DB::statement('ALTER TABLE appointments MODIFY service_name VARCHAR(255) NULL');
        DB::statement('ALTER TABLE appointments MODIFY location_name VARCHAR(255) NULL');
        DB::statement('ALTER TABLE appointments MODIFY doctor_name VARCHAR(255) NULL');
        DB::statement('ALTER TABLE appointments MODIFY agency_name VARCHAR(255) NULL');
    }
};
