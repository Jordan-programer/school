<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Students\CreateStudentRequest;
use App\Http\Requests\V1\Students\UpdateStudentRequest;
use App\Http\Resources\V1\StudentResource;
use App\Models\Guardian;
use App\Models\Student;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

class StudentController extends Controller
{
    /**
     * Listar alunos com filtros, pesquisa e paginação.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Student::class);

        $query = Student::with('guardians')
            ->latest();

        // Pesquisa por nome ou UID
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'ilike', "%{$search}%")
                  ->orWhere('last_name', 'ilike', "%{$search}%")
                  ->orWhere('student_uid', 'ilike', "%{$search}%")
                  ->orWhere('id_document_number', 'ilike', "%{$search}%");
            });
        }

        // Filtro por género
        if ($gender = $request->input('gender')) {
            $query->where('gender', $gender);
        }

        // Filtro por estado activo
        if ($request->has('is_active')) {
            $query->where('is_active', filter_var($request->input('is_active'), FILTER_VALIDATE_BOOLEAN));
        }

        // Filtro por província
        if ($province = $request->input('province')) {
            $query->where('province', $province);
        }

        // Filtro por necessidades especiais
        if ($request->has('has_special_needs')) {
            $query->where('has_special_needs', filter_var($request->input('has_special_needs'), FILTER_VALIDATE_BOOLEAN));
        }

        $students = $query->paginate($request->integer('per_page', 15));

        return StudentResource::collection($students);
    }

    /**
     * Registar novo aluno com encarregado(s) de educação.
     */
    public function store(CreateStudentRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $student = DB::transaction(function () use ($validated) {
            // Gerar UID único para o aluno
            $schoolYear = now()->year;
            $studentUid = Student::generateStudentUid($schoolYear);

            $student = Student::create([
                'student_uid'             => $studentUid,
                'first_name'              => $validated['first_name'],
                'last_name'               => $validated['last_name'],
                'birth_date'              => $validated['birth_date'],
                'gender'                  => $validated['gender'],
                'id_document_type'        => $validated['id_document_type'],
                'id_document_number'      => $validated['id_document_number'] ?? null,
                'has_special_needs'       => $validated['has_special_needs'] ?? false,
                'special_needs_description' => $validated['special_needs_description'] ?? null,
                'province'                => $validated['province'],
                'municipality'            => $validated['municipality'],
                'neighborhood'            => $validated['neighborhood'] ?? null,
                'address'                 => $validated['address'] ?? null,
                'origin_school'           => $validated['origin_school'] ?? null,
                'is_active'               => true,
            ]);

            // Criar e associar encarregados de educação
            foreach ($validated['guardians'] as $guardianData) {
                // Tentar reutilizar encarregado existente (mesmo BI/telefone)
                $guardian = $this->findOrCreateGuardian($guardianData);

                $student->guardians()->attach($guardian->id, [
                    'relationship_type'          => $guardianData['relationship_type'],
                    'is_primary_contact'         => $guardianData['is_primary_contact'] ?? false,
                    'is_financially_responsible' => $guardianData['is_financially_responsible'] ?? false,
                ]);
            }

            activity('students')
                ->performedOn($student)
                ->causedBy(request()->user())
                ->withProperties(['student_uid' => $student->student_uid])
                ->log("Aluno registado: {$student->full_name} ({$student->student_uid}).");

            return $student;
        });

        $student->load('guardians');

        return response()->json([
            'message' => "Aluno {$student->full_name} registado com sucesso. UID: {$student->student_uid}.",
            'student' => new StudentResource($student),
        ], 201);
    }

    /**
     * Mostrar detalhes de um aluno.
     */
    public function show(Student $student): JsonResponse
    {
        $this->authorize('view', $student);

        $student->load('guardians');

        return response()->json([
            'student' => new StudentResource($student),
        ]);
    }

    /**
     * Actualizar dados de um aluno.
     */
    public function update(UpdateStudentRequest $request, Student $student): JsonResponse
    {
        $student->update($request->validated());

        $student->load('guardians');

        activity('students')
            ->performedOn($student)
            ->causedBy($request->user())
            ->log("Dados do aluno {$student->full_name} actualizados.");

        return response()->json([
            'message' => 'Dados do aluno actualizados com sucesso.',
            'student' => new StudentResource($student),
        ]);
    }

    /**
     * Remover aluno (soft delete).
     */
    public function destroy(Request $request, Student $student): JsonResponse
    {
        $this->authorize('delete', $student);

        $name = $student->full_name;
        $student->delete();

        activity('students')
            ->causedBy($request->user())
            ->log("Aluno {$name} removido do sistema.");

        return response()->json([
            'message' => 'Aluno removido do sistema com sucesso.',
        ]);
    }

    /**
     * Activar ou desactivar matrícula do aluno.
     */
    public function toggleStatus(Request $request, Student $student): JsonResponse
    {
        $this->authorize('toggleStatus', $student);

        $student->update(['is_active' => !$student->is_active]);

        $status = $student->is_active ? 'activada' : 'desactivada';

        activity('students')
            ->performedOn($student)
            ->causedBy($request->user())
            ->log("Matrícula do aluno {$student->full_name} {$status}.");

        return response()->json([
            'message'   => "Matrícula do aluno {$status} com sucesso.",
            'is_active' => $student->is_active,
        ]);
    }

    /**
     * Adicionar ou actualizar encarregado de educação de um aluno.
     */
    public function addGuardian(Request $request, Student $student): JsonResponse
    {
        $this->authorize('update', $student);

        $validated = $request->validate([
            'guardian_id'               => ['nullable', 'uuid', 'exists:guardians,id'],
            'full_name'                 => ['required_without:guardian_id', 'string', 'max:200'],
            'id_document_type'          => ['required_without:guardian_id', 'string'],
            'id_document_number'        => ['nullable', 'string', 'max:50'],
            'phone_primary'             => ['required_without:guardian_id', 'string'],
            'phone_secondary'           => ['nullable', 'string'],
            'email'                     => ['nullable', 'email'],
            'profession'                => ['nullable', 'string', 'max:100'],
            'workplace'                 => ['nullable', 'string', 'max:200'],
            'address'                   => ['nullable', 'string', 'max:500'],
            'relationship_type'         => ['required', 'string'],
            'is_primary_contact'        => ['boolean'],
            'is_financially_responsible' => ['boolean'],
        ]);

        $guardian = !empty($validated['guardian_id'])
            ? Guardian::findOrFail($validated['guardian_id'])
            : $this->findOrCreateGuardian($validated);

        // Se já existe a ligação, actualiza o pivot
        if ($student->guardians()->where('guardian_id', $guardian->id)->exists()) {
            $student->guardians()->updateExistingPivot($guardian->id, [
                'relationship_type'          => $validated['relationship_type'],
                'is_primary_contact'         => $validated['is_primary_contact'] ?? false,
                'is_financially_responsible' => $validated['is_financially_responsible'] ?? false,
            ]);
        } else {
            $student->guardians()->attach($guardian->id, [
                'relationship_type'          => $validated['relationship_type'],
                'is_primary_contact'         => $validated['is_primary_contact'] ?? false,
                'is_financially_responsible' => $validated['is_financially_responsible'] ?? false,
            ]);
        }

        $student->load('guardians');

        return response()->json([
            'message' => 'Encarregado de educação associado com sucesso.',
            'student' => new StudentResource($student),
        ]);
    }

    /**
     * Remover associação de encarregado a um aluno.
     */
    public function removeGuardian(Request $request, Student $student, Guardian $guardian): JsonResponse
    {
        $this->authorize('update', $student);

        $count = $student->guardians()->count();

        if ($count <= 1) {
            return response()->json([
                'message' => 'O aluno deve ter pelo menos um encarregado de educação.',
            ], 422);
        }

        $student->guardians()->detach($guardian->id);

        return response()->json([
            'message' => 'Encarregado de educação removido da ficha do aluno.',
        ]);
    }

    /**
     * Encontrar encarregado existente ou criar um novo.
     */
    private function findOrCreateGuardian(array $data): Guardian
    {
        // Tentar encontrar pelo número de documento
        if (!empty($data['id_document_number'])) {
            $existing = Guardian::where('id_document_number', $data['id_document_number'])
                ->where('id_document_type', $data['id_document_type'] ?? 'BI')
                ->first();

            if ($existing) {
                return $existing;
            }
        }

        // Tentar encontrar pelo telefone principal
        if (!empty($data['phone_primary'])) {
            $existing = Guardian::where('phone_primary', $data['phone_primary'])->first();
            if ($existing) {
                return $existing;
            }
        }

        return Guardian::create([
            'full_name'          => $data['full_name'],
            'id_document_type'   => $data['id_document_type'] ?? 'PROVISORIO',
            'id_document_number' => $data['id_document_number'] ?? null,
            'phone_primary'      => $data['phone_primary'],
            'phone_secondary'    => $data['phone_secondary'] ?? null,
            'email'              => $data['email'] ?? null,
            'profession'         => $data['profession'] ?? null,
            'workplace'          => $data['workplace'] ?? null,
            'address'            => $data['address'] ?? null,
        ]);
    }
}
