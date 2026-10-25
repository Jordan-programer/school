<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Módulo 5: Chamada Diária e Registo de Assiduidade Offline.
     */
    public function up(): void
    {
        Schema::create('attendances', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('class_group_id')->constrained('class_groups')->cascadeOnDelete();
            $table->foreignUuid('subject_id')->nullable()->constrained('subjects')->nullOnDelete();
            $table->foreignUuid('academic_year_id')->constrained('academic_years')->cascadeOnDelete();

            $table->date('date');
            $table->unsignedTinyInteger('lesson_number')->default(1); // 1º tempo, 2º tempo, etc.
            $table->string('topic', 255)->nullable(); // Sumário da aula
            $table->text('notes')->nullable(); // Observações da chamada

            $table->foreignUuid('recorded_by_user_id')->constrained('users')->cascadeOnDelete();

            // Sincronização offline
            $table->uuid('client_mutation_id')->nullable()->index(); // Idempotência para submissões offline
            $table->timestamp('synced_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['class_group_id', 'date']);
            $table->index(['date', 'lesson_number']);
        });

        Schema::create('attendance_records', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('attendance_id')->constrained('attendances')->cascadeOnDelete();
            $table->foreignUuid('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignUuid('enrollment_id')->nullable()->constrained('enrollments')->nullOnDelete();

            // Status: PRESENTE, FALTA_INJUSTIFICADA, FALTA_JUSTIFICADA, ATRASO
            $table->string('status', 30)->default('PRESENTE');

            // Justificação de faltas
            $table->string('justification_reason', 255)->nullable();
            $table->timestamp('justified_at')->nullable();
            $table->foreignUuid('justified_by_user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            $table->unique(['attendance_id', 'student_id']);
            $table->index(['student_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_records');
        Schema::dropIfExists('attendances');
    }
};
