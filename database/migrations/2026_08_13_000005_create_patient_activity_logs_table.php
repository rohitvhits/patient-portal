<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('patient_activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_user_id')->nullable()->constrained('patient_users')->nullOnDelete();
            $table->string('action'); // login_success, login_failed, appointment_list_viewed, appointment_detail_viewed,
                                       // document_list_viewed, document_downloaded, unauthorized_access, logout
            $table->string('description')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patient_activity_logs');
    }
};
