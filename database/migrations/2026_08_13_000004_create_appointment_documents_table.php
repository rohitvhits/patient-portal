<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('appointment_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('appointment_id')->constrained('appointments')->cascadeOnDelete();
            $table->unsignedBigInteger('erp_document_id');
            $table->string('document_name')->nullable();
            $table->string('extension', 20)->nullable();
            $table->unsignedBigInteger('size_in_bytes')->nullable();
            $table->timestamps();

            $table->unique(['appointment_id', 'erp_document_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointment_documents');
    }
};
