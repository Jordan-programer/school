<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Alunos
        Schema::create('students', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('student_uid')->unique(); // Identificador único da escola (ex: 2026-ESC1042-0001)
            $table->string('first_name');
            $table->string('last_name');
            $table->date('birth_date');
            $table->enum('gender', ['M', 'F']);
            $table->enum('id_document_type', ['BI', 'CEDULA', 'ASSENTO', 'PROVISORIO'])->default('PROVISORIO');
            $table->string('id_document_number')->nullable();
            $table->string('id_document_file_path')->nullable();
            $table->boolean('has_special_needs')->default(false);
            $table->text('special_needs_description')->nullable();
            $table->string('origin_school')->nullable(); // Escola de proveniência (caso transferência)
            $table->string('province')->default('Luanda');
            $table->string('municipality')->default('Luanda');
            $table->string('neighborhood')->nullable();
            $table->text('address')->nullable();
            $table->string('photo_path')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['last_name', 'first_name']);
            $table->index('id_document_number');
        });

        // Encarregados de Educação
        Schema::create('guardians', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('full_name');
            $table->enum('id_document_type', ['BI', 'CEDULA', 'ASSENTO', 'PROVISORIO'])->default('BI');
            $table->string('id_document_number')->nullable();
            $table->string('phone_primary');
            $table->string('phone_secondary')->nullable();
            $table->string('email')->nullable();
            $table->string('profession')->nullable();
            $table->string('workplace')->nullable();
            $table->text('address')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('phone_primary');
        });

        // Tabela Pivô Aluno <-> Encarregado (N:M)
        Schema::create('student_guardian', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignUuid('guardian_id')->constrained('guardians')->cascadeOnDelete();
            $table->string('relationship_type')->default('Pai'); // Pai, Mãe, Tutor Legal, etc.
            $table->boolean('is_primary_contact')->default(false);
            $table->boolean('is_financially_responsible')->default(false);
            $table->timestamps();

            $table->unique(['student_id', 'guardian_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_guardian');
        Schema::dropIfExists('guardians');
        Schema::dropIfExists('students');
    }
};
