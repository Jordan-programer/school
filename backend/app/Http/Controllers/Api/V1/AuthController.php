<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Auth\LoginRequest;
use App\Http\Resources\V1\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Autenticar utilizador no sistema.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $login = $request->validated('login');
        $password = $request->validated('password');
        $deviceName = $request->validated('device_name') ?? 'Navegador Web';

        // Permitir autenticação tanto por e-mail quanto por telefone angolano (+244...)
        $user = User::with(['roles', 'staff'])
            ->where(function ($query) use ($login) {
                $query->where('email', $login)
                    ->orWhere('phone', $login);
            })
            ->first();

        if (!$user || !Hash::check($password, $user->password)) {
            throw ValidationException::withMessages([
                'login' => ['As credenciais fornecidas estão incorrectas.'],
            ]);
        }

        if (!$user->is_active) {
            return response()->json([
                'message' => 'A sua conta encontra-se desactivada. Por favor, contacte a Direcção ou a Secretaria escolar.',
            ], 403);
        }

        // Actualizar último login
        $user->forceFill(['last_login_at' => now()])->saveQuietly();

        // Gerar token de acesso Sanctum
        $token = $user->createToken($deviceName)->plainTextToken;

        // Auditoria de início de sessão
        activity('auth')
            ->performedOn($user)
            ->causedBy($user)
            ->withProperties(['ip' => $request->ip(), 'user_agent' => $request->userAgent()])
            ->log('Início de sessão efectuado com sucesso.');

        return response()->json([
            'message' => 'Autenticação efectuada com sucesso.',
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => new UserResource($user),
        ]);
    }

    /**
     * Obter dados do utilizador actualmente autenticado.
     */
    public function me(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user()->load(['roles.permissions', 'staff']);

        return response()->json([
            'user' => new UserResource($user),
        ]);
    }

    /**
     * Encerrar sessão e revogar token activo.
     */
    public function logout(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        // Revogar o token actualmente utilizado
        $user->currentAccessToken()->delete();

        activity('auth')
            ->performedOn($user)
            ->causedBy($user)
            ->log('Sessão terminada pelo utilizador.');

        return response()->json([
            'message' => 'Sessão terminada com sucesso.',
        ]);
    }
}
