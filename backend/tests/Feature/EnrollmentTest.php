<?php

use App\Enums\DocumentTypeEnum;
use App\Enums\GenderEnum;
use App\Enums\RoleEnum;
use App\Models\AcademicYear;
use App\Models\ClassGroup;
use App\Models\Enrollment;
use App\Models\Grade;
use App\Models\School;
use App\Models\Shift;
use App\Models\Student;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\AcademicStructureSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\Hash;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(AcademicStructureSeeder::class);
});

// Helper de criação de aluno para testes
function createTestStudent(array $attributes = []): Student
{
    $year = now()->year;
    return Student::create(array_merge([
        'student_uid'        => Student::generateStudentUid($year),
        'first_name'         => 'Lukénia',
        'last_name'          => 'Capitão',
        'birth_date'         => Carbon::parse('2018-05-10'),
        'gender'             => GenderEnum::FEMININO,
        'id_document_type'   => DocumentTypeEnum::BI,
        'id_document_number' => '008928192LA046',
        'province'           => 'Luanda',
        'municipality'       => 'Luanda',
        'is_active'          => true,
    ], $attributes));
}

// ─── 1. Listagem ─────────────────────────────────────────────────────────────
test('secretaria pode listar matrículas com paginação', function () {
    $secretary = User::factory()->create(['password' => Hash::make('test'), 'is_active' => true]);
    $secretary->assignRole(RoleEnum::SECRETARY->value);

    $response = $this->actingAs($secretary)->getJson('/api/v1/enrollments');

    $response->assertStatus(200)
        ->assertJsonStructure(['data', 'links', 'meta']);
});

test('professor não tem permissão para realizar matrículas', function () {
    $teacher = User::factory()->create(['password' => Hash::make('test'), 'is_active' => true]);
    $teacher->assignRole(RoleEnum::TEACHER->value);

    $student = createTestStudent();
    $year = AcademicYear::first();
    $grade = Grade::first();

    $response = $this->actingAs($teacher)->postJson('/api/v1/enrollments', [
        'student_id'       => $student->id,
        'academic_year_id' => $year->id,
        'grade_id'         => $grade->id,
    ]);

    $response->assertStatus(403);
});

// ─── 2. Nova Matrícula ───────────────────────────────────────────────────────
test('secretaria pode registar nova matrícula para aluno de ingresso regular', function () {
    $secretary = User::factory()->create(['password' => Hash::make('test'), 'is_active' => true]);
    $secretary->assignRole(RoleEnum::SECRETARY->value);

    // Aluno de 6 anos para a 1ª classe (regular)
    $student = createTestStudent(['birth_date' => Carbon::parse('2020-03-15')]);
    $year = AcademicYear::first();
    $grade = Grade::where('code', 'like', '%1%')->first() ?? Grade::first();

    $response = $this->actingAs($secretary)->postJson('/api/v1/enrollments', [
        'student_id'       => $student->id,
        'academic_year_id' => $year->id,
        'grade_id'         => $grade->id,
        'enrollment_type'  => 'NOVA_MATRICULA',
    ]);

    $response->assertStatus(201);
    expect($response->json('enrollment.enrollment_code'))->toStartWith('MAT-')
        ->and($response->json('enrollment.has_age_distortion'))->toBeFalse();
});

test('sistema detecta distorção idade-classe sem bloquear a matrícula', function () {
    $secretary = User::factory()->create(['password' => Hash::make('test'), 'is_active' => true]);
    $secretary->assignRole(RoleEnum::SECRETARY->value);

    // Aluno com 14 anos a ingressar na 1ª classe (forte distorção idade-classe)
    $student = createTestStudent(['birth_date' => Carbon::parse('2012-01-10')]);
    $year = AcademicYear::first();
    $grade = Grade::where('code', 'like', '%1%')->first() ?? Grade::first();

    $response = $this->actingAs($secretary)->postJson('/api/v1/enrollments', [
        'student_id'       => $student->id,
        'academic_year_id' => $year->id,
        'grade_id'         => $grade->id,
    ]);

    $response->assertStatus(201);
    expect($response->json('enrollment.has_age_distortion'))->toBeTrue()
        ->and($response->json('enrollment.distortion_details'))->toContain('Distorção idade-classe');
});

test('sistema rejeita matrícula duplicada do mesmo aluno no mesmo ano lectivo', function () {
    $secretary = User::factory()->create(['password' => Hash::make('test'), 'is_active' => true]);
    $secretary->assignRole(RoleEnum::SECRETARY->value);

    $student = createTestStudent();
    $year = AcademicYear::first();
    $grade = Grade::first();

    // Primeira matrícula
    $this->actingAs($secretary)->postJson('/api/v1/enrollments', [
        'student_id'       => $student->id,
        'academic_year_id' => $year->id,
        'grade_id'         => $grade->id,
    ])->assertStatus(201);

    // Segunda tentativa no mesmo ano lectivo
    $second = $this->actingAs($secretary)->postJson('/api/v1/enrollments', [
        'student_id'       => $student->id,
        'academic_year_id' => $year->id,
        'grade_id'         => $grade->id,
    ]);

    $second->assertStatus(422)
        ->assertJsonFragment(['message' => 'O aluno já possui uma matrícula ou confirmação registada para este ano lectivo.']);
});

// ─── 3. Confirmação (Renovação) de Matrícula ────────────────────────────────
test('secretaria pode confirmar matrícula (renovação anual) em ano lectivo seguinte', function () {
    $secretary = User::factory()->create(['password' => Hash::make('test'), 'is_active' => true]);
    $secretary->assignRole(RoleEnum::SECRETARY->value);

    $student = createTestStudent(['birth_date' => Carbon::parse('2019-04-10')]);
    $school = School::first();

    // Ano Lectivo 2025/2026
    $year1 = AcademicYear::first();
    $grade1 = Grade::where('code', 'like', '%1%')->first() ?? Grade::first();

    // Ano Lectivo 2026/2027
    $year2 = AcademicYear::create([
        'school_id'  => $school->id,
        'name'       => '2026/2027',
        'start_date' => '2026-09-01',
        'end_date'   => '2027-06-30',
        'is_active'  => false,
    ]);
    $grade2 = Grade::where('code', 'like', '%2%')->first() ?? Grade::skip(1)->first() ?? $grade1;

    // Matrícula no 1º ano
    $this->actingAs($secretary)->postJson('/api/v1/enrollments', [
        'student_id'       => $student->id,
        'academic_year_id' => $year1->id,
        'grade_id'         => $grade1->id,
    ])->assertStatus(201);

    // Confirmação (Renovação) no 2º ano
    $confirmResponse = $this->actingAs($secretary)->postJson('/api/v1/enrollments/confirm', [
        'student_id'       => $student->id,
        'academic_year_id' => $year2->id,
        'grade_id'         => $grade2->id,
    ]);

    $confirmResponse->assertStatus(201);
    expect($confirmResponse->json('enrollment.enrollment_code'))->toStartWith('CONF-')
        ->and($confirmResponse->json('enrollment.enrollment_type'))->toBe('CONFIRMACAO');
});

// ─── 4. Distribuição em Turmas & Número de Ordem ───────────────────────────
test('secretaria pode atribuir turma ao aluno com geração automática do número de ordem', function () {
    $secretary = User::factory()->create(['password' => Hash::make('test'), 'is_active' => true]);
    $secretary->assignRole(RoleEnum::SECRETARY->value);

    $student1 = createTestStudent(['first_name' => 'Adão']);
    $student2 = createTestStudent(['first_name' => 'Beatriz']);
    $year = AcademicYear::first();
    $grade = Grade::first();
    $shift = Shift::first();
    $school = School::first();

    $classGroup = ClassGroup::create([
        'school_id'        => $school->id,
        'academic_year_id' => $year->id,
        'grade_id'         => $grade->id,
        'shift_id'         => $shift->id,
        'name'             => 'Turma Teste A',
        'max_students'     => 35,
        'is_active'        => true,
    ]);

    // Matricular aluno 1 directamente com a turma
    $enroll1 = $this->actingAs($secretary)->postJson('/api/v1/enrollments', [
        'student_id'       => $student1->id,
        'academic_year_id' => $year->id,
        'grade_id'         => $grade->id,
        'class_group_id'   => $classGroup->id,
    ]);

    $enroll1->assertStatus(201);
    expect($enroll1->json('enrollment.order_number'))->toBe(1);

    // Matricular aluno 2 sem turma
    $enroll2 = $this->actingAs($secretary)->postJson('/api/v1/enrollments', [
        'student_id'       => $student2->id,
        'academic_year_id' => $year->id,
        'grade_id'         => $grade->id,
    ]);
    $enroll2Id = $enroll2->json('enrollment.id');

    // Atribuir turma posteriormente via endpoint assign-class
    $assignResponse = $this->actingAs($secretary)->patchJson("/api/v1/enrollments/{$enroll2Id}/assign-class", [
        'class_group_id' => $classGroup->id,
    ]);

    $assignResponse->assertStatus(200);
    expect($assignResponse->json('enrollment.order_number'))->toBe(2);
});

test('atribuição de turma avisa sobrelotação quando exceder a lotação máxima', function () {
    $secretary = User::factory()->create(['password' => Hash::make('test'), 'is_active' => true]);
    $secretary->assignRole(RoleEnum::SECRETARY->value);

    $student1 = createTestStudent();
    $student2 = createTestStudent();
    $year = AcademicYear::first();
    $grade = Grade::first();
    $shift = Shift::first();
    $school = School::first();

    // Turma com lotação máxima de 1 aluno
    $classGroup = ClassGroup::create([
        'school_id'        => $school->id,
        'academic_year_id' => $year->id,
        'grade_id'         => $grade->id,
        'shift_id'         => $shift->id,
        'name'             => 'Turma Cheia',
        'max_students'     => 1,
        'is_active'        => true,
    ]);

    // Aluno 1 ocupa a única vaga
    $this->actingAs($secretary)->postJson('/api/v1/enrollments', [
        'student_id'       => $student1->id,
        'academic_year_id' => $year->id,
        'grade_id'         => $grade->id,
        'class_group_id'   => $classGroup->id,
    ])->assertStatus(201);

    // Aluno 2 tenta ingressar na turma cheia sem autorização forçada
    $enroll2 = $this->actingAs($secretary)->postJson('/api/v1/enrollments', [
        'student_id'       => $student2->id,
        'academic_year_id' => $year->id,
        'grade_id'         => $grade->id,
    ]);
    $enroll2Id = $enroll2->json('enrollment.id');

    $assignFail = $this->actingAs($secretary)->patchJson("/api/v1/enrollments/{$enroll2Id}/assign-class", [
        'class_group_id' => $classGroup->id,
    ]);

    $assignFail->assertStatus(422)
        ->assertJsonFragment(['capacity_exceeded' => true]);
});

// ─── 5. Cancelamento e Transferência ─────────────────────────────────────────
test('director pode cancelar uma matrícula activa', function () {
    $director = User::factory()->create(['password' => Hash::make('test'), 'is_active' => true]);
    $director->assignRole(RoleEnum::DIRECTOR->value);

    $student = createTestStudent();
    $year = AcademicYear::first();
    $grade = Grade::first();

    $enroll = $this->actingAs($director)->postJson('/api/v1/enrollments', [
        'student_id'       => $student->id,
        'academic_year_id' => $year->id,
        'grade_id'         => $grade->id,
    ]);
    $enrollId = $enroll->json('enrollment.id');

    $cancelResponse = $this->actingAs($director)->patchJson("/api/v1/enrollments/{$enrollId}/cancel");
    $cancelResponse->assertStatus(200);

    $enrollment = Enrollment::find($enrollId);
    expect($enrollment->status->value)->toBe('CANCELADA');
});

test('secretaria pode registar transferência escolar de saída', function () {
    $secretary = User::factory()->create(['password' => Hash::make('test'), 'is_active' => true]);
    $secretary->assignRole(RoleEnum::SECRETARY->value);

    $student = createTestStudent();
    $year = AcademicYear::first();
    $grade = Grade::first();

    $enroll = $this->actingAs($secretary)->postJson('/api/v1/enrollments', [
        'student_id'       => $student->id,
        'academic_year_id' => $year->id,
        'grade_id'         => $grade->id,
    ]);
    $enrollId = $enroll->json('enrollment.id');

    $transferResponse = $this->actingAs($secretary)->postJson("/api/v1/enrollments/{$enrollId}/transfer", [
        'destination_school'    => 'Complexo Escolar Sagrada Esperança',
        'transfer_guide_number' => 'GUIA-2026/881',
        'reason'                => 'Mudança de residência para o município do Cazenga',
        'transfer_date'         => now()->toDateString(),
    ]);

    $transferResponse->assertStatus(200);

    $enrollment = Enrollment::find($enrollId);
    expect($enrollment->status->value)->toBe('TRANSFERIDO');
});

// ─── 6. Estatísticas ────────────────────────────────────────────────────────
test('secretaria pode consultar resumo estatístico de matrículas e distorção idade-classe', function () {
    $secretary = User::factory()->create(['password' => Hash::make('test'), 'is_active' => true]);
    $secretary->assignRole(RoleEnum::SECRETARY->value);

    $response = $this->actingAs($secretary)->getJson('/api/v1/enrollments/stats');

    $response->assertStatus(200)
        ->assertJsonStructure([
            'academic_year_id',
            'total_confirmed',
            'total_new',
            'total_renewals',
            'total_transfers',
            'total_distortion',
            'distortion_percentage',
            'unassigned_count',
            'by_grade',
        ]);
});
