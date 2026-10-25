<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Academics\CreateClassGroupRequest;
use App\Http\Resources\V1\ClassGroupResource;
use App\Models\AcademicYear;
use App\Models\ClassGroup;
use App\Models\Staff;
use App\Models\TeachingAssignment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ClassGroupController extends Controller
{
    /**
     * Listar turmas com paginação e filtros por ano, classe, turno e sala.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', ClassGroup::class);

        $query = ClassGroup::with([
            'academicYear',
            'grade.cycle.level',
            'shift',
            'classroom',
            'teachingAssignments.subject',
            'teachingAssignments.staff',
        ]);

        if ($search = $request->input('search')) {
            $query->where('name', 'ilike', "%{$search}%");
        }

        if ($yearId = $request->input('academic_year_id')) {
            $query->where('academic_year_id', $yearId);
        } else {
            // Por defeito, focar no ano lectivo activo
            $query->whereHas('academicYear', fn($q) => $q->where('is_active', true));
        }

        if ($gradeId = $request->input('grade_id')) {
            $query->where('grade_id', $gradeId);
        }

        if ($shiftId = $request->input('shift_id')) {
            $query->where('shift_id', $shiftId);
        }

        $classGroups = $query->orderBy('name')->paginate($request->integer('per_page', 15));

        return ClassGroupResource::collection($classGroups);
    }

    /**
     * Criar nova turma escolar.
     */
    public function store(CreateClassGroupRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $academicYear = AcademicYear::findOrFail($validated['academic_year_id']);

        $classGroup = ClassGroup::create([
            'school_id' => $academicYear->school_id,
            'academic_year_id' => $validated['academic_year_id'],
            'grade_id' => $validated['grade_id'],
            'shift_id' => $validated['shift_id'],
            'classroom_id' => $validated['classroom_id'] ?? null,
            'name' => $validated['name'],
            'max_students' => $validated['max_students'],
            'is_active' => true,
        ]);

        $classGroup->load(['academicYear', 'grade.cycle.level', 'shift', 'classroom']);

        return response()->json([
            'message' => "Turma {$classGroup->name} criada com sucesso.",
            'class_group' => new ClassGroupResource($classGroup),
        ], 201);
    }

    /**
     * Exibir detalhes de uma turma escolar.
     */
    public function show(ClassGroup $classGroup): JsonResponse
    {
        $this->authorize('view', $classGroup);

        $classGroup->load([
            'academicYear',
            'grade.cycle.level',
            'shift',
            'classroom',
            'teachingAssignments.subject',
            'teachingAssignments.staff',
        ]);

        return response()->json([
            'class_group' => new ClassGroupResource($classGroup),
        ]);
    }

    /**
     * Atribuir professor a uma disciplina nesta turma (serviço docente).
     */
    public function assignTeacher(Request $request, ClassGroup $classGroup): JsonResponse
    {
        $this->authorize('assignTeacher', $classGroup);

        $validated = $request->validate([
            'subject_id' => ['required', 'uuid', 'exists:subjects,id'],
            'staff_id' => ['required', 'uuid', 'exists:staff,id'],
        ], [
            'subject_id.required' => 'A disciplina é obrigatória.',
            'staff_id.required' => 'O professor é obrigatório.',
        ]);

        $staff = Staff::findOrFail($validated['staff_id']);

        $assignment = TeachingAssignment::updateOrCreate(
            [
                'class_group_id' => $classGroup->id,
                'subject_id' => $validated['subject_id'],
            ],
            [
                'academic_year_id' => $classGroup->academic_year_id,
                'staff_id' => $staff->id,
            ]
        );

        $assignment->load(['subject', 'staff']);

        return response()->json([
            'message' => "Professor {$staff->full_name} atribuído com sucesso.",
            'assignment' => new \App\Http\Resources\V1\TeachingAssignmentResource($assignment),
        ]);
    }
}
