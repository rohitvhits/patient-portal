<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Persisted alongside the other synced-from-ERP columns (location_name,
     * doctor_name, service_name) so the appointments list can filter/search
     * by agency at the database level instead of only at render time.
     */
    public function up(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->string('agency_name')->nullable()->after('service_name');
        });
    }

    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropColumn('agency_name');
        });
    }
};
