<?php

use App\Enums\RoleEnum;
use App\Models\Guardian;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\Hash;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

// ─── Helper de criação de aluno ────────────────────────────────────────────
function studentPayload(array $overrides = []): array
{
    return array_merge([
        'first_name'       => 'Mulemba',
        'last_name'        => 'Capitão',
        'birth_date'       => '2015-03-12',
        'gender'           => 'M',
        'id_document_type' => 'ASSENTO',
        'id_document_number' => '1234567AB',
        'province'         => 'Luanda',
        'municipality'     => 'Luanda',
        'guardians'        => [
            [
                'full_name'                 => 'António Capitão',
                'id_document_type'          => 'BI',
                'id_document_number'        => '9876543LA012',
                'phone_primary'             => '+244923456789',
                'relationship_type'         => 'Pai',
                'is_primary_contact'        => true,
                'is_financially_responsible' => true,
            ],
        ],
    ], $overrides);
}

// ─── Listagem ───────────────────────────────────────────────────────────────
test('secretaria pode listar alunos com paginação', function () {
    $secretary = User::factory()->create(['password' => Hash::make('test'), 'is_active' => true]);
    $secretary->assignRole(RoleEnum::SECRETARY->value);

    $response = $this->actingAs($secretary)->getJson('/api/v1/students');

    $response->assertStatus(200)
        ->assertJsonStructure(['data', 'meta', 'links']);
});

test('professor pode listar alunos', function () {
    $teacher = User::factory()->create(['password' => Hash::make('test'), 'is_active' => true]);
    $teacher->assignRole(RoleEnum::TEACHER->value);

    $response = $this->actingAs($teacher)->getJson('/api/v1/students');
    $response->assertStatus(200);
});

// ─── Criação ────────────────────────────────────────────────────────────────
test('secretaria pode registar novo aluno com encarregado', function () {
    $secretary = User::factory()->create(['password' => Hash::make('test'), 'is_active' => true]);
    $secretary->assignRole(RoleEnum::SECRETARY->value);

    $response = $this->actingAs($secretary)->postJson('/api/v1/students', studentPayload());

    $response->assertStatus(201)
        ->assertJsonStructure([
            'message',
            'student' => [
                'id', 'student_uid', 'first_name', 'last_name', 'full_name',
                'birth_date', 'gender', 'gender_label',
                'id_document_type', 'id_document_number',
                'province', 'municipality',
                'is_active', 'guardians',
            ],
        ]);

    // Verificar UID gerado no formato ano-sequência
    $uid = $response->json('student.student_uid');
    expect($uid)->toMatch('/^\d{4}-\d{4}$/');

    // Verificar que o encarregado foi criado e associado
    expect($response->json('student.guardians'))->toHaveCount(1);
    expect($response->json('student.guardians.0.full_name'))->toBe('António Capitão');
    expect($response->json('student.guardians.0.is_primary_contact'))->toBeTrue();

    // Verificar na BD
    expect(Student::count())->toBe(1);
    expect(Guardian::count())->toBe(1);
});

test('encarregado existente não é duplicado ao registar segundo aluno', function () {
    $secretary = User::factory()->create(['password' => Hash::make('test'), 'is_active' => true]);
    $secretary->assignRole(RoleEnum::SECRETARY->value);

    $guardianData = [
        'full_name'          => 'Maria José Silva',
        'id_document_type'   => 'BI',
        'id_document_number' => '5555555LA012',
        'phone_primary'      => '+244934000111',
        'relationship_type'  => 'Mãe',
        'is_primary_contact' => true,
        'is_financially_responsible' => true,
    ];

    // Registar dois alunos com o mesmo encarregado (mesmos documentos)
    $this->actingAs($secretary)->postJson('/api/v1/students', studentPayload([
        'first_name' => 'Kiala', 'guardians' => [$guardianData],
    ]))->assertStatus(201);

    $this->actingAs($secretary)->postJson('/api/v1/students', studentPayload([
        'first_name' => 'Zita', 'guardians' => [$guardianData],
    ]))->assertStatus(201);

    // Deve existir apenas 1 encarregado (sem duplicação)
    expect(Guardian::count())->toBe(1);
    // Os 2 alunos devem estar ligados ao mesmo encarregado
    expect(Student::count())->toBe(2);
    $guardian = Guardian::first();
    expect($guardian->students()->count())->toBe(2);
});

test('professor não pode registar alunos', function () {
    $teacher = User::factory()->create(['password' => Hash::make('test'), 'is_active' => true]);
    $teacher->assignRole(RoleEnum::TEACHER->value);

    $response = $this->actingAs($teacher)->postJson('/api/v1/students', studentPayload());
    $response->assertStatus(403);
});

test('criação de aluno falha sem encarregado', function () {
    $secretary = User::factory()->create(['password' => Hash::make('test'), 'is_active' => true]);
    $secretary->assignRole(RoleEnum::SECRETARY->value);

    $payload = studentPayload();
    unset($payload['guardians']);

    $response = $this->actingAs($secretary)->postJson('/api/v1/students', $payload);
    $response->assertStatus(422)->assertJsonValidationErrors(['guardians']);
});

// ─── Detalhes ───────────────────────────────────────────────────────────────
test('secretaria pode ver detalhes completos de um aluno', function () {
    $secretary = User::factory()->create(['password' => Hash::make('test'), 'is_active' => true]);
    $secretary->assignRole(RoleEnum::SECRETARY->value);

    // Criar aluno
    $createResponse = $this->actingAs($secretary)
        ->postJson('/api/v1/students', studentPayload());
    $studentId = $createResponse->json('student.id');

    $response = $this->actingAs($secretary)->getJson("/api/v1/students/{$studentId}");

    $response->assertStatus(200)
        ->assertJsonPath('student.full_name', 'Mulemba Capitão')
        ->assertJsonPath('student.birth_date', '12/03/2015')
        ->assertJsonPath('student.gender_label', 'Masculino');
});

// ─── Actualização ───────────────────────────────────────────────────────────
test('secretaria pode actualizar dados do aluno', function () {
    $secretary = User::factory()->create(['password' => Hash::make('test'), 'is_active' => true]);
    $secretary->assignRole(RoleEnum::SECRETARY->value);

    $createResponse = $this->actingAs($secretary)
        ->postJson('/api/v1/students', studentPayload());
    $studentId = $createResponse->json('student.id');

    $updateResponse = $this->actingAs($secretary)
        ->patchJson("/api/v1/students/{$studentId}", [
            'neighborhood' => 'Cazenga',
            'municipality' => 'Luanda',
        ]);

    $updateResponse->assertStatus(200)
        ->assertJsonPath('student.municipality', 'Luanda');
});

// ─── Toggle de estado ───────────────────────────────────────────────────────
test('director pode activar e desactivar matrícula de aluno', function () {
    $director = User::factory()->create(['password' => Hash::make('test'), 'is_active' => true]);
    $director->assignRole(RoleEnum::DIRECTOR->value);

    $secretary = User::factory()->create(['password' => Hash::make('test'), 'is_active' => true]);
    $secretary->assignRole(RoleEnum::SECRETARY->value);

    // Criar aluno como secretaria
    $createResponse = $this->actingAs($secretary)
        ->postJson('/api/v1/students', studentPayload());
    $studentId = $createResponse->json('student.id');

    // Director desactiva
    $toggleResponse = $this->actingAs($director)
        ->patchJson("/api/v1/students/{$studentId}/toggle-status");

    $toggleResponse->assertStatus(200)
        ->assertJsonPath('is_active', false);

    // Director reactiva
    $toggleResponse2 = $this->actingAs($director)
        ->patchJson("/api/v1/students/{$studentId}/toggle-status");

    $toggleResponse2->assertStatus(200)
        ->assertJsonPath('is_active', true);
});

// ─── Encarregados ──────────────────────────────────────────────────────────
test('secretaria pode adicionar segundo encarregado ao aluno', function () {
    $secretary = User::factory()->create(['password' => Hash::make('test'), 'is_active' => true]);
    $secretary->assignRole(RoleEnum::SECRETARY->value);

    $createResponse = $this->actingAs($secretary)
        ->postJson('/api/v1/students', studentPayload());
    $studentId = $createResponse->json('student.id');

    $addResponse = $this->actingAs($secretary)->postJson(
        "/api/v1/students/{$studentId}/guardians",
        [
            'full_name'                 => 'Maria Capitão',
            'id_document_type'          => 'BI',
            'id_document_number'        => '4444444LA012',
            'phone_primary'             => '+244912111222',
            'relationship_type'         => 'Mãe',
            'is_primary_contact'        => false,
            'is_financially_responsible' => false,
        ]
    );

    $addResponse->assertStatus(200);
    expect($addResponse->json('student.guardians'))->toHaveCount(2);
});

test('não é possível remover o único encarregado do aluno', function () {
    $secretary = User::factory()->create(['password' => Hash::make('test'), 'is_active' => true]);
    $secretary->assignRole(RoleEnum::SECRETARY->value);

    $createResponse = $this->actingAs($secretary)
        ->postJson('/api/v1/students', studentPayload());
    $studentId = $createResponse->json('student.id');
    $guardianId = $createResponse->json('student.guardians.0.id');

    $removeResponse = $this->actingAs($secretary)
        ->deleteJson("/api/v1/students/{$studentId}/guardians/{$guardianId}");

    $removeResponse->assertStatus(422)
        ->assertJsonPath('message', 'O aluno deve ter pelo menos um encarregado de educação.');
});
