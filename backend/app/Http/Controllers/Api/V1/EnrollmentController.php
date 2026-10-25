<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\EnrollmentStatusEnum;
use App\Enums\EnrollmentTypeEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Enrollments\AssignClassGroupRequest;
use App\Http\Requests\V1\Enrollments\ConfirmEnrollmentRequest;
use App\Http\Requests\V1\Enrollments\CreateEnrollmentRequest;
use App\Http\Requests\V1\Enrollments\TransferStudentRequest;
use App\Http\Resources\V1\EnrollmentResource;
use App\Models\AcademicYear;
use App\Models\ClassGroup;
use App\Models\Enrollment;
use App\Models\Grade;
use App\Models\School;
use App\Models\Student;
use App\Models\StudentTransfer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

class EnrollmentController extends Controller
{
    /**
     * Listar matrículas e confirmações com filtros, busca e paginação.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Enrollment::class);

        $query = Enrollment::with([
            'student.guardians',
            'academicYear',
            'grade',
            'classGroup.shift',
            'classGroup.classroom',
            'school',
        ])->latest('enrolled_at');

        // Pesquisa por nome do aluno, UID ou código de matrícula
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('enrollment_code', 'ilike', "%{$search}%")
                  ->orWhereHas('student', function ($sq) use ($search) {
                      $sq->where('first_name', 'ilike', "%{$search}%")
                        ->orWhere('last_name', 'ilike', "%{$search}%")
                        ->orWhere('student_uid', 'ilike', "%{$search}%")
                        ->orWhere('id_document_number', 'ilike', "%{$search}%");
                  });
            });
        }

        // Filtro por ano lectivo
        if ($academicYearId = $request->input('academic_year_id')) {
            $query->where('academic_year_id', $academicYearId);
        }

        // Filtro por classe
        if ($gradeId = $request->input('grade_id')) {
            $query->where('grade_id', $gradeId);
        }

        // Filtro por turma
        if ($classGroupId = $request->input('class_group_id')) {
            $query->where('class_group_id', $classGroupId);
        }

        // Filtro por tipo de matrícula
        if ($enrollmentType = $request->input('enrollment_type')) {
            $query->where('enrollment_type', $enrollmentType);
        }

        // Filtro por estado
        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        // Filtro por distorção idade-classe
        if ($request->has('has_age_distortion')) {
            $query->where('has_age_distortion', filter_var($request->input('has_age_distortion'), FILTER_VALIDATE_BOOLEAN));
        }

        // Filtro: apenas sem turma atribuída
        if ($request->boolean('unassigned_class')) {
            $query->whereNull('class_group_id');
        }

        $enrollments = $query->paginate($request->integer('per_page', 15));

        return EnrollmentResource::collection($enrollments);
    }

    /**
     * Realizar Nova Matrícula (Ingresso inicial do aluno na escola).
     */
    public function store(CreateEnrollmentRequest $request): JsonResponse
    {
        $validated = $request->validated();

        // 1. Verificar se o aluno já tem matrícula/confirmação neste ano lectivo
        $existing = Enrollment::where('student_id', $validated['student_id'])
            ->where('academic_year_id', $validated['academic_year_id'])
            ->first();

        if ($existing) {
            return response()->json([
                'message' => 'O aluno já possui uma matrícula ou confirmação registada para este ano lectivo.',
            ], 422);
        }

        $student = Student::findOrFail($validated['student_id']);
        $academicYear = AcademicYear::findOrFail($validated['academic_year_id']);
        $grade = Grade::findOrFail($validated['grade_id']);

        // Escola: parâmetro ou padrão
        $schoolId = $validated['school_id'] ?? School::first()?->id;
        if (!$schoolId) {
            return response()->json(['message' => 'Nenhuma escola configurada no sistema.'], 422);
        }

        // Extrair ano numérico para código e idade de referência
        $yearNum = (int) substr($academicYear->name, 0, 4);
        if ($yearNum < 2000) {
            $yearNum = now()->year;
        }

        // 2. Avaliar distorção idade-classe (sem impedir a matrícula)
        $distortion = Enrollment::checkAgeDistortion($student, $grade, $yearNum);

        // 3. Gerar código oficial de matrícula
        $type = $validated['enrollment_type'] ?? EnrollmentTypeEnum::NOVA_MATRICULA->value;
        $enrollmentCode = Enrollment::generateEnrollmentCode($type, $yearNum);

        $enrollment = DB::transaction(function () use ($validated, $student, $academicYear, $grade, $schoolId, $type, $enrollmentCode, $distortion, $request) {
            $enrollment = new Enrollment([
                'student_id'          => $student->id,
                'academic_year_id'    => $academicYear->id,
                'grade_id'            => $grade->id,
                'school_id'           => $schoolId,
                'enrollment_code'     => $enrollmentCode,
                'enrollment_type'     => $type,
                'status'              => EnrollmentStatusEnum::CONFIRMADA,
                'age_at_enrollment'   => $distortion['age'],
                'has_age_distortion'  => $distortion['has_distortion'],
                'distortion_details'  => $distortion['details'],
                'notes'               => $validated['notes'] ?? null,
                'enrolled_at'         => now(),
                'enrolled_by_user_id' => $request->user()->id,
            ]);

            // Se indicou turma, validar e calcular ordem
            if (!empty($validated['class_group_id'])) {
                $classGroup = ClassGroup::findOrFail($validated['class_group_id']);

                if ($classGroup->academic_year_id !== $academicYear->id || $classGroup->grade_id !== $grade->id) {
                    abort(422, 'A turma seleccionada não pertence ao mesmo ano lectivo e classe da matrícula.');
                }

                $enrollment->class_group_id = $classGroup->id;
                $enrollment->order_number = $enrollment->assignNextOrderNumber();
            }

            $enrollment->save();

            activity('enrollments')
                ->performedOn($enrollment)
                ->causedBy($request->user())
                ->withProperties([
                    'enrollment_code' => $enrollment->enrollment_code,
                    'student_uid'     => $student->student_uid,
                ])
                ->log("Nova matrícula efectuada para o aluno {$student->full_name} ({$enrollment->enrollment_code}).");

            return $enrollment;
        });

        $enrollment->load([
            'student.guardians',
            'academicYear',
            'grade',
            'classGroup.shift',
            'classGroup.classroom',
            'school',
            'enrolledBy',
        ]);

        $msg = "Matrícula realizada com sucesso. Código: {$enrollment->enrollment_code}.";
        if ($enrollment->has_age_distortion) {
            $msg .= ' [Atenção pedagógica: detectada distorção idade-classe].';
        }

        return response()->json([
            'message'    => $msg,
            'enrollment' => new EnrollmentResource($enrollment),
        ], 201);
    }

    /**
     * Realizar Confirmação de Matrícula (Renovação anual de aluno existente).
     */
    public function confirm(ConfirmEnrollmentRequest $request): JsonResponse
    {
        $validated = $request->validated();

        // 1. Verificar se já tem matrícula no ano lectivo de destino
        $existing = Enrollment::where('student_id', $validated['student_id'])
            ->where('academic_year_id', $validated['academic_year_id'])
            ->first();

        if ($existing) {
            return response()->json([
                'message' => 'O aluno já tem a matrícula confirmada para este ano lectivo.',
            ], 422);
        }

        $student = Student::findOrFail($validated['student_id']);
        $academicYear = AcademicYear::findOrFail($validated['academic_year_id']);
        $grade = Grade::findOrFail($validated['grade_id']);

        $schoolId = $validated['school_id'] ?? School::first()?->id;

        $yearNum = (int) substr($academicYear->name, 0, 4);
        if ($yearNum < 2000) {
            $yearNum = now()->year;
        }

        // 2. Avaliar distorção idade-classe
        $distortion = Enrollment::checkAgeDistortion($student, $grade, $yearNum);

        // 3. Gerar código de confirmação oficial (CONF-2026-XXXX)
        $enrollmentCode = Enrollment::generateEnrollmentCode(EnrollmentTypeEnum::CONFIRMACAO, $yearNum);

        $enrollment = DB::transaction(function () use ($validated, $student, $academicYear, $grade, $schoolId, $enrollmentCode, $distortion, $request) {
            $enrollment = new Enrollment([
                'student_id'          => $student->id,
                'academic_year_id'    => $academicYear->id,
                'grade_id'            => $grade->id,
                'school_id'           => $schoolId,
                'enrollment_code'     => $enrollmentCode,
                'enrollment_type'     => EnrollmentTypeEnum::CONFIRMACAO,
                'status'              => EnrollmentStatusEnum::CONFIRMADA,
                'age_at_enrollment'   => $distortion['age'],
                'has_age_distortion'  => $distortion['has_distortion'],
                'distortion_details'  => $distortion['details'],
                'notes'               => $validated['notes'] ?? null,
                'enrolled_at'         => now(),
                'enrolled_by_user_id' => $request->user()->id,
            ]);

            if (!empty($validated['class_group_id'])) {
                $classGroup = ClassGroup::findOrFail($validated['class_group_id']);

                if ($classGroup->academic_year_id !== $academicYear->id || $classGroup->grade_id !== $grade->id) {
                    abort(422, 'A turma seleccionada não pertence ao mesmo ano lectivo e classe da confirmação.');
                }

                $enrollment->class_group_id = $classGroup->id;
                $enrollment->order_number = $enrollment->assignNextOrderNumber();
            }

            $enrollment->save();

            activity('enrollments')
                ->performedOn($enrollment)
                ->causedBy($request->user())
                ->withProperties([
                    'enrollment_code' => $enrollment->enrollment_code,
                    'student_uid'     => $student->student_uid,
                ])
                ->log("Confirmação de matrícula efectuada para o aluno {$student->full_name} ({$enrollment->enrollment_code}).");

            return $enrollment;
        });

        $enrollment->load([
            'student.guardians',
            'academicYear',
            'grade',
            'classGroup.shift',
            'classGroup.classroom',
            'school',
            'enrolledBy',
        ]);

        return response()->json([
            'message'    => "Confirmação de matrícula realizada com sucesso. Código: {$enrollment->enrollment_code}.",
            'enrollment' => new EnrollmentResource($enrollment),
        ], 201);
    }

    /**
     * Ver detalhes da matrícula (incluindo comprovativo).
     */
    public function show(Enrollment $enrollment): JsonResponse
    {
        $this->authorize('view', $enrollment);

        $enrollment->load([
            'student.guardians',
            'academicYear',
            'grade.cycle.level',
            'classGroup.shift',
            'classGroup.classroom',
            'school',
            'enrolledBy',
            'transfers.registeredBy',
        ]);

        return response()->json([
            'enrollment' => new EnrollmentResource($enrollment),
        ]);
    }

    /**
     * Atribuir, mudar ou desassociar turma do aluno matriculado.
     */
    public function assignClass(AssignClassGroupRequest $request, Enrollment $enrollment): JsonResponse
    {
        $classGroupId = $request->input('class_group_id');
        $forceCapacity = $request->boolean('force_capacity', false);

        if ($classGroupId) {
            $classGroup = ClassGroup::findOrFail($classGroupId);

            // Validar compatibilidade de ano e classe
            if ($classGroup->academic_year_id !== $enrollment->academic_year_id) {
                return response()->json(['message' => 'A turma pertence a um ano lectivo diferente da matrícula.'], 422);
            }

            if ($classGroup->grade_id !== $enrollment->grade_id) {
                return response()->json(['message' => 'A turma pertence a uma classe diferente da matrícula do aluno.'], 422);
            }

            // Verificar capacidade da turma
            $currentCount = Enrollment::where('class_group_id', $classGroup->id)
                ->where('id', '!=', $enrollment->id)
                ->where('status', EnrollmentStatusEnum::CONFIRMADA)
                ->count();

            if ($currentCount >= $classGroup->max_students && !$forceCapacity) {
                return response()->json([
                    'message'           => "A turma {$classGroup->name} atingiu a lotação máxima ({$classGroup->max_students} alunos).",
                    'capacity_exceeded' => true,
                    'current_count'     => $currentCount,
                    'max_students'      => $classGroup->max_students,
                ], 422);
            }

            $enrollment->class_group_id = $classGroup->id;
            $enrollment->assignNextOrderNumber();
        } else {
            // Desassociar turma
            $enrollment->class_group_id = null;
            $enrollment->order_number = null;
        }

        $enrollment->save();

        activity('enrollments')
            ->performedOn($enrollment)
            ->causedBy($request->user())
            ->log("Turma do aluno actualizada na matrícula {$enrollment->enrollment_code}.");

        $enrollment->load([
            'student',
            'academicYear',
            'grade',
            'classGroup.shift',
            'classGroup.classroom',
            'school',
        ]);

        return response()->json([
            'message'    => 'Distribuição de turma actualizada com sucesso.',
            'enrollment' => new EnrollmentResource($enrollment),
        ]);
    }

    /**
     * Cancelar / anular matrícula.
     */
    public function cancel(Request $request, Enrollment $enrollment): JsonResponse
    {
        $this->authorize('cancel', $enrollment);

        $enrollment->update([
            'status' => EnrollmentStatusEnum::CANCELADA,
        ]);

        activity('enrollments')
            ->performedOn($enrollment)
            ->causedBy($request->user())
            ->log("Matrícula {$enrollment->enrollment_code} foi cancelada.");

        return response()->json([
            'message' => 'Matrícula cancelada com sucesso.',
        ]);
    }

    /**
     * Registar transferência escolar do aluno.
     */
    public function transfer(TransferStudentRequest $request, Enrollment $enrollment): JsonResponse
    {
        $validated = $request->validated();

        DB::transaction(function () use ($validated, $enrollment, $request) {
            StudentTransfer::create([
                'student_id'            => $enrollment->student_id,
                'enrollment_id'         => $enrollment->id,
                'academic_year_id'      => $enrollment->academic_year_id,
                'transfer_type'         => 'SAIDA',
                'destination_school'    => $validated['destination_school'],
                'transfer_guide_number' => $validated['transfer_guide_number'] ?? null,
                'reason'                => $validated['reason'] ?? null,
                'transfer_date'         => $validated['transfer_date'],
                'registered_by_user_id' => $request->user()->id,
            ]);

            $enrollment->update([
                'status' => EnrollmentStatusEnum::TRANSFERIDO,
            ]);

            activity('enrollments')
                ->performedOn($enrollment)
                ->causedBy($request->user())
                ->log("Transferência de saída registada para {$validated['destination_school']}.");
        });

        return response()->json([
            'message' => "Transferência para a instituição \"{$validated['destination_school']}\" registada com sucesso.",
        ]);
    }

    /**
     * Métricas e estatísticas gerais de matrículas para o painel de controlo.
     */
    public function stats(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Enrollment::class);

        $yearId = $request->input('academic_year_id');
        if (!$yearId) {
            $yearId = AcademicYear::where('is_active', true)->value('id') ?? AcademicYear::latest()->value('id');
        }

        $baseQuery = Enrollment::where('academic_year_id', $yearId);

        $totalConfirmed = (clone $baseQuery)->where('status', EnrollmentStatusEnum::CONFIRMADA)->count();
        $totalNew = (clone $baseQuery)->where('enrollment_type', EnrollmentTypeEnum::NOVA_MATRICULA)->count();
        $totalRenewals = (clone $baseQuery)->where('enrollment_type', EnrollmentTypeEnum::CONFIRMACAO)->count();
        $totalTransfers = (clone $baseQuery)->where('status', EnrollmentStatusEnum::TRANSFERIDO)->count();

        // Distorção idade-classe
        $totalDistortion = (clone $baseQuery)->where('has_age_distortion', true)->count();
        $distortionPercent = $totalConfirmed > 0 ? round(($totalDistortion / $totalConfirmed) * 100, 1) : 0;

        // Sem turma atribuída (pendentes de enturmação)
        $unassignedCount = (clone $baseQuery)->where('status', EnrollmentStatusEnum::CONFIRMADA)->whereNull('class_group_id')->count();

        // Distribuição por classe
        $byGrade = Grade::withCount(['enrollments' => function ($q) use ($yearId) {
            $q->where('academic_year_id', $yearId)->where('status', EnrollmentStatusEnum::CONFIRMADA);
        }])->orderBy('order_index')->get()->map(function ($g) {
            return [
                'grade_id'   => $g->id,
                'grade_name' => $g->name,
                'count'      => $g->enrollments_count,
            ];
        });

        return response()->json([
            'academic_year_id'      => $yearId,
            'total_confirmed'       => $totalConfirmed,
            'total_new'             => $totalNew,
            'total_renewals'        => $totalRenewals,
            'total_transfers'       => $totalTransfers,
            'total_distortion'      => $totalDistortion,
            'distortion_percentage' => $distortionPercent,
            'unassigned_count'      => $unassignedCount,
            'by_grade'              => $byGrade,
        ]);
    }
}
