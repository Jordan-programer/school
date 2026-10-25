<?php

use App\Enums\AttendanceStatusEnum;
use App\Enums\DocumentTypeEnum;
use App\Enums\EnrollmentStatusEnum;
use App\Enums\EnrollmentTypeEnum;
use App\Enums\GenderEnum;
use App\Enums\RoleEnum;
use App\Models\AcademicYear;
use App\Models\Attendance;
use App\Models\AttendanceRecord;
use App\Models\ClassGroup;
use App\Models\Enrollment;
use App\Models\Grade;
use App\Models\School;
use App\Models\Shift;
use App\Models\Student;
use App\Models\Subject;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\AcademicStructureSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(AcademicStructureSeeder::class);
});

// Helper de criação de ambiente com turma e alunos matriculados
function setupClassWithStudents(int $numStudents = 3): array
{
    $school = School::first();
    $year = AcademicYear::first();
    $grade = Grade::first();
    $shift = Shift::first();

    $classGroup = ClassGroup::create([
        'school_id'        => $school->id,
        'academic_year_id' => $year->id,
        'grade_id'         => $grade->id,
        'shift_id'         => $shift->id,
        'name'             => 'Turma A - Teste Chamada',
        'max_students'     => 30,
        'is_active'        => true,
    ]);

    $students = [];
    for ($i = 1; $i <= $numStudents; $i++) {
        $student = Student::create([
            'student_uid'        => sprintf('PROC-2026-%04d', 9000 + $i),
            'first_name'         => "Aluno{$i}",
            'last_name'          => 'Silva',
            'birth_date'         => Carbon::parse('2018-01-10'),
            'gender'             => GenderEnum::MASCULINO,
            'id_document_type'   => DocumentTypeEnum::BI,
            'id_document_number' => sprintf('00991823%dLA048', $i),
            'province'           => 'Luanda',
            'municipality'       => 'Luanda',
            'is_active'          => true,
        ]);

        $enrollment = Enrollment::create([
            'student_id'          => $student->id,
            'academic_year_id'    => $year->id,
            'grade_id'            => $grade->id,
            'class_group_id'      => $classGroup->id,
            'school_id'           => $school->id,
            'enrollment_code'     => sprintf('MAT-2026-%04d', 9000 + $i),
            'enrollment_type'     => EnrollmentTypeEnum::NOVA_MATRICULA,
            'status'              => EnrollmentStatusEnum::CONFIRMADA,
            'order_number'        => $i,
            'age_at_enrollment'   => 8,
            'has_age_distortion'  => false,
            'enrolled_at'         => now(),
        ]);

        $students[] = [
            'student'    => $student,
            'enrollment' => $enrollment,
        ];
    }

    return [
        'classGroup' => $classGroup,
        'students'   => $students,
        'year'       => $year,
    ];
}

// ─── 1. Folha de Chamada (Relação Nominal) ──────────────────────────────────
test('professor pode obter a folha de chamada com alunos ordenados', function () {
    $teacher = User::factory()->create(['password' => Hash::make('test'), 'is_active' => true]);
    $teacher->assignRole(RoleEnum::TEACHER->value);

    $setup = setupClassWithStudents(3);

    $response = $this->actingAs($teacher)->getJson(
        "/api/v1/attendances/sheet?class_group_id={$setup['classGroup']->id}&lesson_number=1"
    );

    $response->assertStatus(200)
        ->assertJsonStructure([
            'class_group' => ['id', 'name', 'total_students'],
            'session'     => ['date', 'lesson_number', 'is_already_recorded'],
            'sheet'       => [
                '*' => ['student_id', 'enrollment_id', 'order_number', 'full_name', 'current_status'],
            ],
        ]);

    expect($response->json('sheet'))->toHaveCount(3)
        ->and($response->json('sheet.0.order_number'))->toBe(1)
        ->and($response->json('sheet.1.order_number'))->toBe(2);
});

// ─── 2. Registo de Chamada Diária ───────────────────────────────────────────
test('professor pode salvar chamada diária com presenças e faltas', function () {
    $teacher = User::factory()->create(['password' => Hash::make('test'), 'is_active' => true]);
    $teacher->assignRole(RoleEnum::TEACHER->value);

    $setup = setupClassWithStudents(2);
    $student1 = $setup['students'][0]['student'];
    $student2 = $setup['students'][1]['student'];

    $payload = [
        'class_group_id' => $setup['classGroup']->id,
        'date'           => now()->toDateString(),
        'lesson_number'  => 1,
        'topic'          => 'Introdução aos Números Naturais',
        'records'        => [
            [
                'student_id' => $student1->id,
                'status'     => AttendanceStatusEnum::PRESENTE->value,
            ],
            [
                'student_id' => $student2->id,
                'status'     => AttendanceStatusEnum::FALTA_INJUSTIFICADA->value,
            ],
        ],
    ];

    $response = $this->actingAs($teacher)->postJson('/api/v1/attendances', $payload);

    $response->assertStatus(201)
        ->assertJsonStructure(['message', 'attendance' => ['id', 'stats']]);

    expect($response->json('attendance.stats.present'))->toBe(1)
        ->and($response->json('attendance.stats.unjustified'))->toBe(1);
});

test('salvar chamada para mesmo tempo e data actualiza o registo em vez de duplicar', function () {
    $teacher = User::factory()->create(['password' => Hash::make('test'), 'is_active' => true]);
    $teacher->assignRole(RoleEnum::TEACHER->value);

    $setup = setupClassWithStudents(2);
    $student1 = $setup['students'][0]['student'];
    $student2 = $setup['students'][1]['student'];

    // 1ª submissão: aluno 2 com falta injustificada
    $this->actingAs($teacher)->postJson('/api/v1/attendances', [
        'class_group_id' => $setup['classGroup']->id,
        'date'           => now()->toDateString(),
        'lesson_number'  => 2,
        'records'        => [
            ['student_id' => $student1->id, 'status' => 'PRESENTE'],
            ['student_id' => $student2->id, 'status' => 'FALTA_INJUSTIFICADA'],
        ],
    ])->assertStatus(201);

    // 2ª submissão (correcção do professor): aluno 2 chegou atrasado (ATRASO)
    $updateResponse = $this->actingAs($teacher)->postJson('/api/v1/attendances', [
        'class_group_id' => $setup['classGroup']->id,
        'date'           => now()->toDateString(),
        'lesson_number'  => 2,
        'records'        => [
            ['student_id' => $student1->id, 'status' => 'PRESENTE'],
            ['student_id' => $student2->id, 'status' => 'ATRASO'],
        ],
    ]);

    $updateResponse->assertStatus(201);
    expect($updateResponse->json('attendance.stats.late'))->toBe(1)
        ->and($updateResponse->json('attendance.stats.unjustified'))->toBe(0);

    // Confirma que não duplicou a sessão
    $count = Attendance::where('class_group_id', $setup['classGroup']->id)
        ->whereDate('date', now())
        ->where('lesson_number', 2)
        ->count();
    expect($count)->toBe(1);
});

// ─── 3. Sincronização em Lote Offline ───────────────────────────────────────
test('sincronização offline em lote processa múltiplas sessões com idempotência', function () {
    $teacher = User::factory()->create(['password' => Hash::make('test'), 'is_active' => true]);
    $teacher->assignRole(RoleEnum::TEACHER->value);

    $setup = setupClassWithStudents(2);
    $student1 = $setup['students'][0]['student'];

    $mutation1 = (string) Str::uuid();
    $mutation2 = (string) Str::uuid();

    $syncPayload = [
        'mutations' => [
            [
                'client_mutation_id' => $mutation1,
                'class_group_id'     => $setup['classGroup']->id,
                'date'               => '2026-03-10',
                'lesson_number'      => 1,
                'topic'              => 'Aula Offline 1',
                'records'            => [
                    ['student_id' => $student1->id, 'status' => 'PRESENTE'],
                ],
            ],
            [
                'client_mutation_id' => $mutation2,
                'class_group_id'     => $setup['classGroup']->id,
                'date'               => '2026-03-10',
                'lesson_number'      => 2,
                'topic'              => 'Aula Offline 2',
                'records'            => [
                    ['student_id' => $student1->id, 'status' => 'PRESENTE'],
                ],
            ],
        ],
    ];

    // Primeira sincronização
    $syncResponse = $this->actingAs($teacher)->postJson('/api/v1/attendances/sync-offline', $syncPayload);

    $syncResponse->assertStatus(200);
    expect($syncResponse->json('synced_count'))->toBe(2);

    // Segunda chamada com as mesmas mutations (teste de idempotência)
    $repeatSync = $this->actingAs($teacher)->postJson('/api/v1/attendances/sync-offline', $syncPayload);
    $repeatSync->assertStatus(200);
    expect($repeatSync->json('synced_count'))->toBe(0); // não duplica
});

// ─── 4. Justificação de Faltas ──────────────────────────────────────────────
test('secretaria ou director de turma pode justificar falta com motivo', function () {
    $secretary = User::factory()->create(['password' => Hash::make('test'), 'is_active' => true]);
    $secretary->assignRole(RoleEnum::SECRETARY->value);

    $setup = setupClassWithStudents(1);
    $student = $setup['students'][0]['student'];

    // Criar registo de chamada com falta injustificada
    $attendance = Attendance::create([
        'class_group_id'      => $setup['classGroup']->id,
        'academic_year_id'    => $setup['year']->id,
        'date'                => now()->toDateString(),
        'lesson_number'       => 1,
        'recorded_by_user_id' => $secretary->id,
    ]);

    $record = AttendanceRecord::create([
        'attendance_id' => $attendance->id,
        'student_id'    => $student->id,
        'status'        => AttendanceStatusEnum::FALTA_INJUSTIFICADA,
    ]);

    // Secretaria justifica a falta
    $justifyResponse = $this->actingAs($secretary)->patchJson("/api/v1/attendance-records/{$record->id}/justify", [
        'justification_reason' => 'Atestado Médico do Centro de Saúde da Maianga',
    ]);

    $justifyResponse->assertStatus(200);
    expect($justifyResponse->json('record.status'))->toBe('FALTA_JUSTIFICADA')
        ->and($justifyResponse->json('record.justification_reason'))->toContain('Atestado Médico');
});

// ─── 5. Resumo e Alerta Angolano de Assiduidade ─────────────────────────────
test('resumo de assiduidade do aluno calcula percentagem e emite alerta quando assiduidade inferior a 75%', function () {
    $secretary = User::factory()->create(['password' => Hash::make('test'), 'is_active' => true]);
    $secretary->assignRole(RoleEnum::SECRETARY->value);

    $setup = setupClassWithStudents(1);
    $student = $setup['students'][0]['student'];

    // Criar 10 aulas: 4 presenças e 6 faltas injustificadas (40% assiduidade -> ALERTA de risco)
    for ($i = 1; $i <= 10; $i++) {
        $att = Attendance::create([
            'class_group_id'      => $setup['classGroup']->id,
            'academic_year_id'    => $setup['year']->id,
            'date'                => Carbon::parse(sprintf('2026-03-%02d', $i)),
            'lesson_number'       => 1,
            'recorded_by_user_id' => $secretary->id,
        ]);

        AttendanceRecord::create([
            'attendance_id' => $att->id,
            'student_id'    => $student->id,
            'status'        => $i <= 4 ? AttendanceStatusEnum::PRESENTE : AttendanceStatusEnum::FALTA_INJUSTIFICADA,
        ]);
    }

    $summaryResponse = $this->actingAs($secretary)->getJson("/api/v1/attendances/student-summary/{$student->id}");

    $summaryResponse->assertStatus(200)
        ->assertJsonStructure([
            'student' => ['id', 'full_name'],
            'summary' => [
                'total_sessions',
                'present_count',
                'unjustified_count',
                'attendance_rate',
                'at_risk_of_exclusion',
                'risk_message',
            ],
        ]);

    expect($summaryResponse->json('summary.total_sessions'))->toBe(10)
        ->and($summaryResponse->json('summary.present_count'))->toBe(4)
        ->and($summaryResponse->json('summary.attendance_rate'))->toEqual(40)
        ->and($summaryResponse->json('summary.at_risk_of_exclusion'))->toBeTrue()
        ->and($summaryResponse->json('summary.risk_message'))->toContain('risco de exclusão');
});
