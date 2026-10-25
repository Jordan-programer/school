<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Anos Lectivos (ex: 2026)
        Schema::create('academic_years', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('school_id')->constrained('schools')->cascadeOnDelete();
            $table->string('name'); // ex: "Ano Lectivo 2026"
            $table->date('start_date');
            $table->date('end_date');
            $table->boolean('is_active')->default(false);
            $table->boolean('is_closed')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['school_id', 'name']);
        });

        // 3 Trimestres por Ano Lectivo com Prazo do Conselho de Notas
        Schema::create('terms', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('academic_year_id')->constrained('academic_years')->cascadeOnDelete();
            $table->unsignedSmallInteger('term_number'); // 1, 2 ou 3
            $table->string('name'); // ex: "1º Trimestre"
            $table->date('start_date');
            $table->date('end_date');
            $table->date('grades_deadline')->nullable(); // Data limite de lançamento de notas do conselho
            $table->boolean('is_closed')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['academic_year_id', 'term_number']);
        });

        // Níveis de Ensino (Iniciação, Primário, 1º Ciclo do Secundário)
        Schema::create('levels', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('code')->unique(); // INICIACAO, PRIMARIO, PRIMEIRO_CICLO_SECUNDARIO
            $table->string('name');
            $table->unsignedSmallInteger('order_index')->default(1);
            $table->timestamps();
            $table->softDeletes();
        });

        // Ciclos de Aprendizagem (Primário em 3 ciclos de 2 classes: 1ª-2ª, 3ª-4ª, 5ª-6ª)
        Schema::create('cycles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('level_id')->constrained('levels')->cascadeOnDelete();
            $table->unsignedSmallInteger('cycle_number'); // 1, 2, 3
            $table->string('name'); // ex: "1º Ciclo do Primário"
            $table->unsignedSmallInteger('order_index')->default(1);
            $table->timestamps();
            $table->softDeletes();
        });

        // Classes (Iniciação, 1ª a 9ª Classe)
        Schema::create('grades', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('cycle_id')->constrained('cycles')->cascadeOnDelete();
            $table->string('code')->unique(); // INI, 1A, 2A, 3A, 4A, 5A, 6A, 7A, 8A, 9A
            $table->string('name'); // ex: "1ª Classe", "7ª Classe"
            $table->unsignedSmallInteger('order_index')->default(1);
            $table->boolean('is_cycle_ending')->default(false); // True para 2ª, 4ª, 6ª e 9ª classes (avaliação de ciclo)
            $table->timestamps();
            $table->softDeletes();
        });

        // Disciplinas Curriculares
        Schema::create('subjects', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('code')->unique(); // ex: PORT, MAT, CNAT, GEO, HIST
            $table->string('name'); // ex: "Língua Portuguesa", "Matemática"
            $table->foreignUuid('level_id')->nullable()->constrained('levels')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        // Planos Curriculares por Ano Lectivo e Classe
        Schema::create('curriculum_plans', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('academic_year_id')->constrained('academic_years')->cascadeOnDelete();
            $table->foreignUuid('grade_id')->constrained('grades')->cascadeOnDelete();
            $table->foreignUuid('subject_id')->constrained('subjects')->cascadeOnDelete();
            $table->unsignedSmallInteger('weekly_hours')->default(4); // Tempos lectivos semanais
            $table->boolean('is_core')->default(false); // Disciplina nuclear (ex: Português e Matemática)
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['academic_year_id', 'grade_id', 'subject_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('curriculum_plans');
        Schema::dropIfExists('subjects');
        Schema::dropIfExists('grades');
        Schema::dropIfExists('cycles');
        Schema::dropIfExists('levels');
        Schema::dropIfExists('terms');
        Schema::dropIfExists('academic_years');
    }
};
