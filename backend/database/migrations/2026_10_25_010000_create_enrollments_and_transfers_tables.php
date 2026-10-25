<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Módulo 4: Matrículas, Confirmações e Transferências Escolares.
     */
    public function up(): void
    {
        Schema::create('enrollments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignUuid('academic_year_id')->constrained('academic_years')->cascadeOnDelete();
            $table->foreignUuid('grade_id')->constrained('grades')->cascadeOnDelete();
            $table->foreignUuid('class_group_id')->nullable()->constrained('class_groups')->nullOnDelete();
            $table->foreignUuid('school_id')->constrained('schools')->cascadeOnDelete();

            // Código único da Matrícula/Confirmação (ex: MAT-2026-0001 ou CONF-2026-0001)
            $table->string('enrollment_code', 30)->unique();
            $table->string('enrollment_type', 30)->default('NOVA_MATRICULA'); // NOVA_MATRICULA, CONFIRMACAO, TRANSFERENCIA
            $table->string('status', 30)->default('CONFIRMADA'); // CONFIRMADA, PENDENTE, CANCELADA, TRANSFERIDO

            // Número de ordem do aluno na turma (1, 2, 3...)
            $table->unsignedSmallInteger('order_number')->nullable();

            // Distorção Idade-Classe
            $table->unsignedSmallInteger('age_at_enrollment');
            $table->boolean('has_age_distortion')->default(false);
            $table->string('distortion_details', 255)->nullable();

            // Metadados
            $table->text('notes')->nullable();
            $table->timestamp('enrolled_at');
            $table->foreignUuid('enrolled_by_user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            // Um aluno só pode ter 1 matrícula activa por ano lectivo
            $table->unique(['student_id', 'academic_year_id', 'deleted_at'], 'unique_student_year_enrollment');
            $table->index(['academic_year_id', 'grade_id']);
            $table->index(['class_group_id', 'order_number']);
            $table->index(['status', 'enrollment_type']);
        });

        Schema::create('student_transfers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignUuid('enrollment_id')->nullable()->constrained('enrollments')->nullOnDelete();
            $table->foreignUuid('academic_year_id')->constrained('academic_years')->cascadeOnDelete();

            // Tipo: SAIDA (da nossa escola para outra) ou ENTRADA (de outra escola para a nossa)
            $table->string('transfer_type', 20)->default('SAIDA'); // SAIDA, ENTRADA
            $table->string('transfer_guide_number', 50)->nullable(); // Guia de transferência oficial
            $table->string('destination_school', 200)->nullable(); // Escola para onde vai
            $table->string('origin_school', 200)->nullable(); // Escola de onde veio
            $table->string('reason', 255)->nullable(); // Mudança de residência, etc.
            $table->date('transfer_date');

            $table->foreignUuid('registered_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_transfers');
        Schema::dropIfExists('enrollments');
    }
};
