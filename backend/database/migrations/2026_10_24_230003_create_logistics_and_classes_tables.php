<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Turnos Escolares (Manhã, Tarde, Vespertino)
        Schema::create('shifts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('school_id')->constrained('schools')->cascadeOnDelete();
            $table->string('name'); // ex: "Manhã", "Tarde", "Vespertino"
            $table->time('start_time')->default('07:30:00');
            $table->time('end_time')->default('12:45:00');
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['school_id', 'name']);
        });

        // Salas de Aula Físicas
        Schema::create('classrooms', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('school_id')->constrained('schools')->cascadeOnDelete();
            $table->string('name'); // ex: "Sala 01", "Laboratório de Ciências"
            $table->unsignedSmallInteger('capacity')->default(45);
            $table->string('location_block')->nullable(); // ex: "Bloco A", "Piso 1"
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['school_id', 'name']);
        });

        // Turmas por Ano Lectivo (ex: "7ª A", "1ª B")
        Schema::create('class_groups', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignUuid('academic_year_id')->constrained('academic_years')->cascadeOnDelete();
            $table->foreignUuid('grade_id')->constrained('grades')->cascadeOnDelete();
            $table->foreignUuid('shift_id')->constrained('shifts')->cascadeOnDelete();
            $table->foreignUuid('classroom_id')->nullable()->constrained('classrooms')->nullOnDelete();
            $table->string('name'); // ex: "7ª A", "1ª B"
            $table->unsignedSmallInteger('max_students')->default(45);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['academic_year_id', 'name']);
        });

        // Atribuição de Serviço Docente (Professor -> Turma -> Disciplina)
        Schema::create('teaching_assignments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('academic_year_id')->constrained('academic_years')->cascadeOnDelete();
            $table->foreignUuid('class_group_id')->constrained('class_groups')->cascadeOnDelete();
            $table->foreignUuid('subject_id')->constrained('subjects')->cascadeOnDelete();
            $table->foreignUuid('staff_id')->constrained('staff')->cascadeOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['class_group_id', 'subject_id']);
        });

        // Adicionar school_id à tabela staff se não existir
        if (Schema::hasTable('staff') && !Schema::hasColumn('staff', 'school_id')) {
            Schema::table('staff', function (Blueprint $table) {
                $table->foreignUuid('school_id')->nullable()->after('user_id')->constrained('schools')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('teaching_assignments');
        Schema::dropIfExists('class_groups');
        Schema::dropIfExists('classrooms');
        Schema::dropIfExists('shifts');

        if (Schema::hasTable('staff') && Schema::hasColumn('staff', 'school_id')) {
            Schema::table('staff', function (Blueprint $table) {
                $table->dropConstrainedForeignId('school_id');
            });
        }
    }
};
