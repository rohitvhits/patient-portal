<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Local cache of appointment data pulled from the ERP API on each visit
     * to the appointments pages. `patient_user_id` is what ownership checks
     * are enforced against — never trust the erp_appointment_id alone.
     */
    public function up(): void
    {
        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_user_id')->constrained('patient_users')->cascadeOnDelete();
            $table->unsignedBigInteger('erp_appointment_id');
            $table->string('appointment_date')->nullable();
            $table->string('appointment_time')->nullable();
            $table->string('status')->nullable();
            $table->string('location_name')->nullable();
            $table->string('doctor_name')->nullable();
            $table->string('service_name')->nullable();
            $table->timestamps();

            $table->unique(['patient_user_id', 'erp_appointment_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointments');
    }
};
