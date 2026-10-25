<?php

use App\Enums\RoleEnum;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

test('director pode listar contas de utilizadores com paginação e filtros', function () {
    $director = User::create([
        'name' => 'Director Principal',
        'email' => 'dir@escola.ao',
        'password' => Hash::make('Senha123!'),
        'is_active' => true,
    ]);
    $director->assignRole(RoleEnum::DIRECTOR->value);

    Sanctum::actingAs($director);

    $response = $this->getJson('/api/v1/users');

    $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                '*' => ['id', 'name', 'email', 'is_active', 'roles'],
            ],
            'links',
            'meta',
        ]);
});

test('director pode cadastrar novo utilizador e associar staff', function () {
    $director = User::create([
        'name' => 'Director Geral',
        'email' => 'dir.geral@escola.ao',
        'password' => Hash::make('Senha123!'),
        'is_active' => true,
    ]);
    $director->assignRole(RoleEnum::DIRECTOR->value);

    Sanctum::actingAs($director);

    $payload = [
        'name' => 'Afonso Muxima',
        'email' => 'afonso@escola.ao',
        'phone' => '+244923887766',
        'password' => 'Professor2026!',
        'role' => RoleEnum::TEACHER->value,
        'staff' => [
            'staff_code' => 'DOC-999',
            'id_document_type' => 'BI',
            'id_document_number' => '009876543LA021',
            'position' => 'Professor de História',
            'academic_degree' => 'Licenciatura em História de África',
            'specialty' => 'Ciências Sociais',
        ],
    ];

    $response = $this->postJson('/api/v1/users', $payload);

    $response->assertStatus(201)
        ->assertJsonPath('user.name', 'Afonso Muxima')
        ->assertJsonPath('user.roles.0', RoleEnum::TEACHER->value)
        ->assertJsonPath('user.staff.staff_code', 'DOC-999');

    $this->assertDatabaseHas('users', ['email' => 'afonso@escola.ao']);
    $this->assertDatabaseHas('staff', ['staff_code' => 'DOC-999']);
});

test('professor não tem permissão para cadastrar novos utilizadores', function () {
    $teacher = User::create([
        'name' => 'Professor Sem Acesso',
        'email' => 'prof@escola.ao',
        'password' => Hash::make('Senha123!'),
        'is_active' => true,
    ]);
    $teacher->assignRole(RoleEnum::TEACHER->value);

    Sanctum::actingAs($teacher);

    $response = $this->postJson('/api/v1/users', [
        'name' => 'Novo Utilizador',
        'email' => 'novo@escola.ao',
        'password' => 'Senha123!',
        'role' => RoleEnum::STUDENT->value,
    ]);

    $response->assertStatus(403);
});

test('gestor provincial pode visualizar mas não pode criar utilizadores', function () {
    $inspector = User::create([
        'name' => 'Inspector Provincial',
        'email' => 'inspector@governo.ao',
        'password' => Hash::make('Senha123!'),
        'is_active' => true,
    ]);
    $inspector->assignRole(RoleEnum::PROVINCIAL_INSPECTOR->value);

    Sanctum::actingAs($inspector);

    // Pode visualizar listagem
    $viewResponse = $this->getJson('/api/v1/users');
    $viewResponse->assertStatus(200);

    // Bloqueado na criação
    $createResponse = $this->postJson('/api/v1/users', [
        'name' => 'Tentativa Indevida',
        'email' => 'tentativa@escola.ao',
        'password' => 'Senha123!',
        'role' => RoleEnum::TEACHER->value,
    ]);
    $createResponse->assertStatus(403);
});

test('director pode alternar estado de activação de uma conta mas não pode desactivar a si mesmo', function () {
    $director = User::create([
        'name' => 'Director Decisivo',
        'email' => 'decisivo@escola.ao',
        'password' => Hash::make('Senha123!'),
        'is_active' => true,
    ]);
    $director->assignRole(RoleEnum::DIRECTOR->value);

    $targetUser = User::create([
        'name' => 'Funcionario Alvo',
        'email' => 'alvo@escola.ao',
        'password' => Hash::make('Senha123!'),
        'is_active' => true,
    ]);
    $targetUser->assignRole(RoleEnum::SECRETARY->value);

    Sanctum::actingAs($director);

    // Desactivar alvo
    $responseToggle = $this->patchJson("/api/v1/users/{$targetUser->id}/toggle-status");
    $responseToggle->assertStatus(200)
        ->assertJsonPath('is_active', false);

    expect($targetUser->fresh()->is_active)->toBeFalse();

    // Tentar desactivar a própria conta deve ser proibido (Policy 403)
    $selfToggleResponse = $this->patchJson("/api/v1/users/{$director->id}/toggle-status");
    $selfToggleResponse->assertStatus(403);
});
