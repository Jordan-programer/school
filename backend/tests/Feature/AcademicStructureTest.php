<?php

use App\Enums\RoleEnum;
use App\Models\AcademicYear;
use App\Models\ClassGroup;
use App\Models\Classroom;
use App\Models\Grade;
use App\Models\School;
use App\Models\Shift;
use App\Models\Staff;
use App\Models\Subject;
use App\Models\Term;
use App\Models\User;
use Database\Seeders\AcademicStructureSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\UsersAndStaffSeeder;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(UsersAndStaffSeeder::class);
    $this->seed(AcademicStructureSeeder::class);
});

test('utilizador autenticado pode consultar a matriz da estrutura escolar angolana', function () {
    $user = User::where('email', 'director@sisgestesc.ao')->first();
    Sanctum::actingAs($user);

    $response = $this->getJson('/api/v1/academic-structure');

    $response->assertStatus(200)
        ->assertJsonStructure([
            'school' => ['id', 'name', 'code', 'province'],
            'academic_years' => [
                '*' => ['id', 'name', 'is_active', 'terms'],
            ],
            'levels',
            'grades' => [
                '*' => ['id', 'name', 'code', 'is_cycle_ending'],
            ],
            'subjects',
            'shifts',
            'classrooms',
        ]);

    // Verificar se as classes de fim de ciclo estão marcadas correctamente
    $grades = collect($response->json('grades'));
    $grade2A = $grades->firstWhere('code', '2A');
    $grade4A = $grades->firstWhere('code', '4A');
    $grade6A = $grades->firstWhere('code', '6A');
    $grade7A = $grades->firstWhere('code', '7A');

    expect($grade2A['is_cycle_ending'])->toBeTrue();
    expect($grade4A['is_cycle_ending'])->toBeTrue();
    expect($grade6A['is_cycle_ending'])->toBeTrue();
    expect($grade7A['is_cycle_ending'])->toBeFalse();
});

test('director pode listar turmas escolares e filtrar por turno', function () {
    $director = User::where('email', 'director@sisgestesc.ao')->first();
    Sanctum::actingAs($director);

    $shiftManha = Shift::where('name', 'Manhã')->first();

    $response = $this->getJson("/api/v1/class-groups?shift_id={$shiftManha->id}");

    $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                '*' => ['id', 'name', 'max_students', 'grade', 'shift', 'classroom'],
            ],
            'links',
            'meta',
        ]);

    $data = $response->json('data');
    expect(count($data))->toBeGreaterThan(0);
    expect($data[0]['shift']['id'])->toBe($shiftManha->id);
});

test('director pode criar nova turma escolar', function () {
    $director = User::where('email', 'director@sisgestesc.ao')->first();
    Sanctum::actingAs($director);

    $year = AcademicYear::where('is_active', true)->first();
    $grade = Grade::where('code', '7A')->first();
    $shift = Shift::where('name', 'Tarde')->first();
    $room = Classroom::where('name', 'Sala 05')->first();

    $payload = [
        'academic_year_id' => $year->id,
        'grade_id' => $grade->id,
        'shift_id' => $shift->id,
        'classroom_id' => $room->id,
        'name' => '7ª Classe C (Tarde)',
        'max_students' => 45,
    ];

    $response = $this->postJson('/api/v1/class-groups', $payload);

    $response->assertStatus(201)
        ->assertJsonPath('class_group.name', '7ª Classe C (Tarde)')
        ->assertJsonPath('class_group.max_students', 45);

    $this->assertDatabaseHas('class_groups', ['name' => '7ª Classe C (Tarde)']);
});

test('subdirector pedagógico pode atribuir professor a disciplina na turma', function () {
    $pedagogico = User::where('email', 'pedagogico@sisgestesc.ao')->first();
    Sanctum::actingAs($pedagogico);

    $turma = ClassGroup::where('name', '7ª Classe A')->first();
    $subPort = Subject::where('code', 'PORT')->first();
    $professorStaff = Staff::where('staff_code', 'DOC-001')->first();

    $response = $this->postJson("/api/v1/class-groups/{$turma->id}/assign-teacher", [
        'subject_id' => $subPort->id,
        'staff_id' => $professorStaff->id,
    ]);

    $response->assertStatus(200)
        ->assertJsonPath('assignment.subject.code', 'PORT')
        ->assertJsonPath('assignment.teacher.staff_code', 'DOC-001');

    $this->assertDatabaseHas('teaching_assignments', [
        'class_group_id' => $turma->id,
        'subject_id' => $subPort->id,
        'staff_id' => $professorStaff->id,
    ]);
});

test('professor não pode criar turmas escolares nem alterar distribuições', function () {
    $teacher = User::where('email', 'professor.matematica@sisgestesc.ao')->first();
    Sanctum::actingAs($teacher);

    $year = AcademicYear::first();
    $grade = Grade::first();
    $shift = Shift::first();

    $createResponse = $this->postJson('/api/v1/class-groups', [
        'academic_year_id' => $year->id,
        'grade_id' => $grade->id,
        'shift_id' => $shift->id,
        'name' => 'Turma Proibida',
        'max_students' => 30,
    ]);

    $createResponse->assertStatus(403);
});

test('modelo Term valida bloqueio de notas após data limite do conselho', function () {
    $year = AcademicYear::first();

    $termFechado = Term::create([
        'academic_year_id' => $year->id,
        'term_number' => 4,
        'name' => 'Trimestre Teste Fechado',
        'start_date' => '2026-01-01',
        'end_date' => '2026-03-01',
        'grades_deadline' => now()->subDay()->toDateString(),
        'is_closed' => false,
    ]);

    $termAberto = Term::create([
        'academic_year_id' => $year->id,
        'term_number' => 5,
        'name' => 'Trimestre Teste Aberto',
        'start_date' => '2026-03-02',
        'end_date' => '2026-06-01',
        'grades_deadline' => now()->addDays(10)->toDateString(),
        'is_closed' => false,
    ]);

    expect($termFechado->isGradesDeadlinePassed())->toBeTrue();
    expect($termAberto->isGradesDeadlinePassed())->toBeFalse();
});
