<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('staff', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('staff_code')->unique(); // ex: DOC-001, ADM-002
            $table->string('full_name');
            $table->string('id_document_type')->default('BI'); // BI, CEDULA, ASSENTO, PROVISORIO
            $table->string('id_document_number')->nullable();
            $table->string('position'); // Professor, Director, etc.
            $table->string('academic_degree')->nullable(); // Licenciatura, Mestrado, etc.
            $table->string('specialty')->nullable(); // Matemática, Língua Portuguesa, etc.
            $table->string('phone_primary')->nullable();
            $table->string('phone_secondary')->nullable();
            $table->string('email')->nullable();
            $table->date('hire_date')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('staff');
    }
};
