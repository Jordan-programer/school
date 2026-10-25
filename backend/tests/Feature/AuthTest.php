<?php

use App\Enums\RoleEnum;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\Hash;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

test('utilizador pode autenticar-se com e-mail e palavra-passe válidos', function () {
    $user = User::create([
        'name' => 'Manuel António',
        'email' => 'manuel@escola.ao',
        'phone' => '+244923112233',
        'password' => Hash::make('Segredo123!'),
        'is_active' => true,
    ]);
    $user->assignRole(RoleEnum::DIRECTOR->value);

    $response = $this->postJson('/api/v1/auth/login', [
        'login' => 'manuel@escola.ao',
        'password' => 'Segredo123!',
    ]);

    $response->assertStatus(200)
        ->assertJsonStructure([
            'message',
            'access_token',
            'token_type',
            'user' => ['id', 'name', 'email', 'roles', 'permissions'],
        ]);

    expect($response->json('user.roles'))->toContain(RoleEnum::DIRECTOR->value);
});

test('utilizador pode autenticar-se com número de telefone angolano', function () {
    $user = User::create([
        'name' => 'Kiala Bumba',
        'email' => 'kiala@escola.ao',
        'phone' => '+244945112233',
        'password' => Hash::make('Professor2026!'),
        'is_active' => true,
    ]);
    $user->assignRole(RoleEnum::TEACHER->value);

    $response = $this->postJson('/api/v1/auth/login', [
        'login' => '+244945112233',
        'password' => 'Professor2026!',
    ]);

    $response->assertStatus(200)
        ->assertJsonPath('user.name', 'Kiala Bumba');
});

test('recusa autenticação com palavra-passe incorrecta', function () {
    User::create([
        'name' => 'Ana Paula',
        'email' => 'ana@escola.ao',
        'password' => Hash::make('SenhaCerta123!'),
        'is_active' => true,
    ]);

    $response = $this->postJson('/api/v1/auth/login', [
        'login' => 'ana@escola.ao',
        'password' => 'SenhaErrada!',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['login']);
});

test('bloqueia login de utilizador com conta desactivada', function () {
    $user = User::create([
        'name' => 'Utilizador Bloqueado',
        'email' => 'bloqueado@escola.ao',
        'password' => Hash::make('Senha123!'),
        'is_active' => false,
    ]);
    $user->assignRole(RoleEnum::TEACHER->value);

    $response = $this->postJson('/api/v1/auth/login', [
        'login' => 'bloqueado@escola.ao',
        'password' => 'Senha123!',
    ]);

    $response->assertStatus(403)
        ->assertJsonPath('message', 'A sua conta encontra-se desactivada. Por favor, contacte a Direcção ou a Secretaria escolar.');
});

test('utilizador autenticado pode obter o seu perfil me e terminar sessão', function () {
    $user = User::create([
        'name' => 'Maria Zau',
        'email' => 'maria@escola.ao',
        'password' => Hash::make('Secretaria123!'),
        'is_active' => true,
    ]);
    $user->assignRole(RoleEnum::SECRETARY->value);

    $token = $user->createToken('TesteToken')->plainTextToken;

    // Testar /me
    $meResponse = $this->withHeader('Authorization', 'Bearer ' . $token)
        ->getJson('/api/v1/auth/me');

    $meResponse->assertStatus(200)
        ->assertJsonPath('user.email', 'maria@escola.ao')
        ->assertJsonPath('user.roles.0', RoleEnum::SECRETARY->value);

    // Testar /logout
    $logoutResponse = $this->withHeader('Authorization', 'Bearer ' . $token)
        ->postJson('/api/v1/auth/logout');

    $logoutResponse->assertStatus(200)
        ->assertJsonPath('message', 'Sessão terminada com sucesso.');
});
