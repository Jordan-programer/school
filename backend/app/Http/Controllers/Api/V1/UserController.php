<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Users\CreateUserRequest;
use App\Http\Requests\V1\Users\UpdateUserRequest;
use App\Http\Resources\V1\UserResource;
use App\Models\Staff;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    /**
     * Listar utilizadores com filtros e paginação.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', User::class);

        $query = User::with(['roles', 'staff'])
            ->latest();

        // Filtro de pesquisa textual
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'ilike', "%{$search}%")
                    ->orWhere('email', 'ilike', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        // Filtro por papel funcional
        if ($role = $request->input('role')) {
            $query->whereHas('roles', fn($q) => $q->where('name', $role));
        }

        // Filtro por estado activo/inactivo
        if ($request->has('is_active')) {
            $query->where('is_active', filter_var($request->input('is_active'), FILTER_VALIDATE_BOOLEAN));
        }

        $users = $query->paginate($request->integer('per_page', 15));

        return UserResource::collection($users);
    }

    /**
     * Cadastrar novo utilizador e associar perfil.
     */
    public function store(CreateUserRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $user = DB::transaction(function () use ($validated) {
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'] ?? null,
                'password' => Hash::make($validated['password']),
                'is_active' => $validated['is_active'] ?? true,
            ]);

            $user->syncRoles([$validated['role']]);

            // Se forem enviados dados de funcionário/docente
            if (!empty($validated['staff'])) {
                Staff::create([
                    'user_id' => $user->id,
                    'staff_code' => $validated['staff']['staff_code'],
                    'full_name' => $validated['name'],
                    'id_document_type' => $validated['staff']['id_document_type'] ?? 'BI',
                    'id_document_number' => $validated['staff']['id_document_number'] ?? null,
                    'position' => $validated['staff']['position'],
                    'academic_degree' => $validated['staff']['academic_degree'] ?? null,
                    'specialty' => $validated['staff']['specialty'] ?? null,
                    'phone_primary' => $validated['phone'] ?? null,
                    'email' => $validated['email'],
                    'hire_date' => $validated['staff']['hire_date'] ?? now()->toDateString(),
                    'is_active' => true,
                ]);
            }

            return $user;
        });

        $user->load(['roles', 'staff']);

        return response()->json([
            'message' => 'Utilizador registado com sucesso.',
            'user' => new UserResource($user),
        ], 201);
    }

    /**
     * Obter detalhes de um utilizador específico.
     */
    public function show(User $user): JsonResponse
    {
        $this->authorize('view', $user);

        $user->load(['roles.permissions', 'staff']);

        return response()->json([
            'user' => new UserResource($user),
        ]);
    }

    /**
     * Actualizar informações de um utilizador.
     */
    public function update(UpdateUserRequest $request, User $user): JsonResponse
    {
        $validated = $request->validated();

        if (!empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        $user->update($validated);

        if (!empty($validated['role'])) {
            $user->syncRoles([$validated['role']]);
        }

        $user->load(['roles', 'staff']);

        return response()->json([
            'message' => 'Conta de utilizador actualizada com sucesso.',
            'user' => new UserResource($user),
        ]);
    }

    /**
     * Desactivar ou activar conta de utilizador.
     */
    public function toggleStatus(Request $request, User $user): JsonResponse
    {
        $this->authorize('toggleStatus', $user);

        $newStatus = !$user->is_active;
        $user->update(['is_active' => $newStatus]);

        $actionText = $newStatus ? 'activada' : 'desactivada';

        activity('users')
            ->performedOn($user)
            ->causedBy($request->user())
            ->log("A conta do utilizador {$user->name} foi {$actionText}.");

        return response()->json([
            'message' => "Conta do utilizador {$actionText} com sucesso.",
            'is_active' => $user->is_active,
        ]);
    }

    /**
     * Remover logicamente (soft delete) utilizador.
     */
    public function destroy(User $user): JsonResponse
    {
        $this->authorize('delete', $user);

        $user->delete();

        return response()->json([
            'message' => 'Utilizador removido do sistema com sucesso.',
        ]);
    }
}
