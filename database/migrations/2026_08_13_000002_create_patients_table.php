<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Local cache of the ERP patient identity/identities matched to a
     * patient_user's mobile number at OTP-verify time (one mobile can match
     * more than one ERP patient record, e.g. shared family phone).
     */
    public function up(): void
    {
        Schema::create('patients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_user_id')->constrained('patient_users')->cascadeOnDelete();
            $table->unsignedBigInteger('erp_patient_id');
            $table->unsignedBigInteger('agency_id')->nullable();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->date('dob')->nullable();
            $table->timestamps();

            $table->unique(['patient_user_id', 'erp_patient_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patients');
    }
};
