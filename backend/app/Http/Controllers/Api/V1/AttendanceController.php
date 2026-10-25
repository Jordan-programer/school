<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\AttendanceStatusEnum;
use App\Enums\EnrollmentStatusEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Attendance\JustifyAbsenceRequest;
use App\Http\Requests\V1\Attendance\SaveAttendanceRequest;
use App\Http\Requests\V1\Attendance\SyncOfflineAttendanceRequest;
use App\Http\Resources\V1\AttendanceRecordResource;
use App\Http\Resources\V1\AttendanceResource;
use App\Models\AcademicYear;
use App\Models\Attendance;
use App\Models\AttendanceRecord;
use App\Models\ClassGroup;
use App\Models\Enrollment;
use App\Models\Student;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

class AttendanceController extends Controller
{
    /**
     * Listar sessões de chamada com paginação e filtros.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Attendance::class);

        $query = Attendance::with([
            'classGroup.grade',
            'subject',
            'recordedBy',
            'records.student',
        ])->latest('date')->latest('lesson_number');

        if ($classGroupId = $request->input('class_group_id')) {
            $query->where('class_group_id', $classGroupId);
        }

        if ($subjectId = $request->input('subject_id')) {
            $query->where('subject_id', $subjectId);
        }

        if ($date = $request->input('date')) {
            $query->whereDate('date', $date);
        }

        if ($fromDate = $request->input('from_date')) {
            $query->whereDate('date', '>=', $fromDate);
        }

        if ($toDate = $request->input('to_date')) {
            $query->whereDate('date', '<=', $toDate);
        }

        $attendances = $query->paginate($request->integer('per_page', 15));

        return AttendanceResource::collection($attendances);
    }

    /**
     * Obter a Folha de Chamada (Relação Nominal dos Alunos da Turma) para preenchimento.
     */
    public function sheet(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Attendance::class);

        $validated = $request->validate([
            'class_group_id' => ['required', 'uuid', 'exists:class_groups,id'],
            'date'           => ['nullable', 'date'],
            'lesson_number'  => ['nullable', 'integer', 'between:1,10'],
            'subject_id'     => ['nullable', 'uuid', 'exists:subjects,id'],
        ]);

        $classGroup = ClassGroup::with(['grade', 'shift', 'classroom', 'academicYear'])
            ->findOrFail($validated['class_group_id']);

        $date = $validated['date'] ?? now()->toDateString();
        $lessonNumber = $validated['lesson_number'] ?? 1;
        $subjectId = $validated['subject_id'] ?? null;

        // 1. Obter todos os alunos matriculados e activos na turma, ordenados por número de ordem
        $enrollments = Enrollment::with('student')
            ->where('class_group_id', $classGroup->id)
            ->where('status', EnrollmentStatusEnum::CONFIRMADA)
            ->orderBy('order_number')
            ->get();

        // 2. Verificar se já existe registo de chamada lançado para este tempo
        $attendanceQuery = Attendance::with('records')
            ->where('class_group_id', $classGroup->id)
            ->whereDate('date', $date)
            ->where('lesson_number', $lessonNumber);

        if ($subjectId) {
            $attendanceQuery->where('subject_id', $subjectId);
        }

        $existingAttendance = $attendanceQuery->first();

        // Mapear registos existentes
        $recordsMap = [];
        if ($existingAttendance) {
            foreach ($existingAttendance->records as $r) {
                $recordsMap[$r->student_id] = [
                    'record_id'            => $r->id,
                    'status'               => $r->status->value,
                    'status_label'         => $r->status->label(),
                    'abbreviation'         => $r->status->abbreviation(),
                    'justification_reason' => $r->justification_reason,
                    'is_justified'         => ($r->status === AttendanceStatusEnum::FALTA_JUSTIFICADA),
                ];
            }
        }

        // Construir folha de alunos
        $studentsSheet = $enrollments->map(function ($enrollment) use ($recordsMap) {
            $student = $enrollment->student;
            $record = $recordsMap[$student->id] ?? null;

            return [
                'student_id'           => $student->id,
                'enrollment_id'        => $enrollment->id,
                'order_number'         => $enrollment->order_number,
                'student_uid'          => $student->student_uid,
                'full_name'            => $student->full_name,
                'gender'               => $student->gender?->value ?? $student->gender,
                'photo_url'            => $student->photo_path ? asset("storage/{$student->photo_path}") : null,
                'current_status'       => $record ? $record['status'] : AttendanceStatusEnum::PRESENTE->value,
                'current_abbreviation' => $record ? $record['abbreviation'] : 'P',
                'record_id'            => $record ? $record['record_id'] : null,
                'justification_reason' => $record ? $record['justification_reason'] : null,
            ];
        });

        return response()->json([
            'class_group' => [
                'id'            => $classGroup->id,
                'name'          => $classGroup->name,
                'grade_name'    => $classGroup->grade?->name,
                'shift_name'    => $classGroup->shift?->name,
                'total_students'=> $enrollments->count(),
            ],
            'session' => [
                'date'           => $date,
                'date_formatted' => Carbon::parse($date)->format('d/m/Y'),
                'lesson_number'  => (int) $lessonNumber,
                'subject_id'     => $subjectId,
                'is_already_recorded' => !is_null($existingAttendance),
                'attendance_id'  => $existingAttendance?->id,
                'topic'          => $existingAttendance?->topic,
                'notes'          => $existingAttendance?->notes,
            ],
            'sheet' => $studentsSheet,
        ]);
    }

    /**
     * Salvar a chamada diária (presenças e faltas da aula).
     */
    public function store(SaveAttendanceRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $classGroup = ClassGroup::findOrFail($validated['class_group_id']);
        $academicYearId = $validated['academic_year_id'] ?? $classGroup->academic_year_id;

        $attendance = DB::transaction(function () use ($validated, $classGroup, $academicYearId, $request) {
            // Verificar se já existe para este tempo e turma
            $attendanceQuery = Attendance::where('class_group_id', $classGroup->id)
                ->whereDate('date', $validated['date'])
                ->where('lesson_number', $validated['lesson_number']);

            if (!empty($validated['subject_id'])) {
                $attendanceQuery->where('subject_id', $validated['subject_id']);
            }

            $attendance = $attendanceQuery->first();

            if (!$attendance) {
                $attendance = Attendance::create([
                    'class_group_id'     => $classGroup->id,
                    'subject_id'         => $validated['subject_id'] ?? null,
                    'academic_year_id'   => $academicYearId,
                    'date'               => $validated['date'],
                    'lesson_number'      => $validated['lesson_number'],
                    'topic'              => $validated['topic'] ?? null,
                    'notes'              => $validated['notes'] ?? null,
                    'recorded_by_user_id'=> $request->user()->id,
                    'client_mutation_id' => $validated['client_mutation_id'] ?? null,
                    'synced_at'          => now(),
                ]);
            } else {
                $attendance->update([
                    'topic'              => $validated['topic'] ?? $attendance->topic,
                    'notes'              => $validated['notes'] ?? $attendance->notes,
                    'recorded_by_user_id'=> $request->user()->id,
                    'synced_at'          => now(),
                ]);
            }

            // Gravar ou actualizar os registos de cada aluno
            foreach ($validated['records'] as $recordData) {
                AttendanceRecord::updateOrCreate(
                    [
                        'attendance_id' => $attendance->id,
                        'student_id'    => $recordData['student_id'],
                    ],
                    [
                        'enrollment_id'        => $recordData['enrollment_id'] ?? null,
                        'status'               => $recordData['status'],
                        'justification_reason' => $recordData['justification_reason'] ?? null,
                    ]
                );
            }

            activity('attendances')
                ->performedOn($attendance)
                ->causedBy($request->user())
                ->log("Chamada realizada na turma {$classGroup->name} ({$validated['lesson_number']}º tempo).");

            return $attendance;
        });

        $attendance->load(['classGroup.grade', 'subject', 'recordedBy', 'records.student']);

        return response()->json([
            'message'    => 'Chamada diária registada com sucesso.',
            'attendance' => new AttendanceResource($attendance),
        ], 201);
    }

    /**
     * Sincronização em Lote de Chamadas Realizadas Offline.
     */
    public function syncOffline(SyncOfflineAttendanceRequest $request): JsonResponse
    {
        $mutations = $request->validated()['mutations'];
        $syncedCount = 0;
        $processedIds = [];

        DB::transaction(function () use ($mutations, $request, &$syncedCount, &$processedIds) {
            foreach ($mutations as $item) {
                $mutationId = $item['client_mutation_id'];

                // Idempotência: se já processado com este mutation_id, saltar ou actualizar
                $existing = Attendance::where('client_mutation_id', $mutationId)->first();
                if ($existing) {
                    $processedIds[] = $mutationId;
                    continue;
                }

                $classGroup = ClassGroup::find($item['class_group_id']);
                if (!$classGroup) continue;

                $academicYearId = $item['academic_year_id'] ?? $classGroup->academic_year_id;

                $attendance = Attendance::create([
                    'class_group_id'     => $classGroup->id,
                    'subject_id'         => $item['subject_id'] ?? null,
                    'academic_year_id'   => $academicYearId,
                    'date'               => $item['date'],
                    'lesson_number'      => $item['lesson_number'],
                    'topic'              => $item['topic'] ?? null,
                    'notes'              => $item['notes'] ?? null,
                    'recorded_by_user_id'=> $request->user()->id,
                    'client_mutation_id' => $mutationId,
                    'synced_at'          => now(),
                ]);

                foreach ($item['records'] as $recordData) {
                    AttendanceRecord::create([
                        'attendance_id'        => $attendance->id,
                        'student_id'           => $recordData['student_id'],
                        'enrollment_id'        => $recordData['enrollment_id'] ?? null,
                        'status'               => $recordData['status'],
                        'justification_reason' => $recordData['justification_reason'] ?? null,
                    ]);
                }

                $syncedCount++;
                $processedIds[] = $mutationId;
            }
        });

        return response()->json([
            'message'       => "Sincronização concluída: {$syncedCount} sessões de chamada gravadas no servidor.",
            'synced_count'  => $syncedCount,
            'processed_ids' => $processedIds,
        ]);
    }

    /**
     * Justificar falta de um aluno.
     */
    public function justify(JustifyAbsenceRequest $request, AttendanceRecord $record): JsonResponse
    {
        $validated = $request->validated();

        $record->update([
            'status'               => AttendanceStatusEnum::FALTA_JUSTIFICADA,
            'justification_reason' => $validated['justification_reason'],
            'justified_at'         => now(),
            'justified_by_user_id' => $request->user()->id,
        ]);

        activity('attendances')
            ->performedOn($record)
            ->causedBy($request->user())
            ->log("Falta justificada para o aluno ID {$record->student_id}: {$validated['justification_reason']}.");

        $record->load(['student', 'justifiedBy']);

        return response()->json([
            'message' => 'Falta do aluno justificada com sucesso.',
            'record'  => new AttendanceRecordResource($record),
        ]);
    }

    /**
     * Resumo individual de assiduidade do aluno com cálculo de percentagem e alertas angolanos.
     */
    public function studentSummary(Request $request, Student $student): JsonResponse
    {
        $this->authorize('viewAny', Attendance::class);

        $academicYearId = $request->input('academic_year_id');
        $query = AttendanceRecord::where('student_id', $student->id);

        if ($academicYearId) {
            $query->whereHas('attendance', function ($q) use ($academicYearId) {
                $q->where('academic_year_id', $academicYearId);
            });
        }

        $records = $query->with('attendance.subject')->get();
        $totalSessions = $records->count();

        $presentCount     = $records->where('status', AttendanceStatusEnum::PRESENTE)->count();
        $unjustifiedCount = $records->where('status', AttendanceStatusEnum::FALTA_INJUSTIFICADA)->count();
        $justifiedCount   = $records->where('status', AttendanceStatusEnum::FALTA_JUSTIFICADA)->count();
        $lateCount        = $records->where('status', AttendanceStatusEnum::ATRASO)->count();

        // Taxa de assiduidade (% de presenças)
        // No regulamento angolano, presença + justificadas contam para a assiduidade regular
        $effectivePresences = $presentCount + $justifiedCount;
        $attendanceRate = $totalSessions > 0 ? round(($effectivePresences / $totalSessions) * 100, 1) : 100.0;

        // Limite de faltas injustificadas (ex: > 15% das aulas dadas = risco de reprovação por faltas)
        $unjustifiedRate = $totalSessions > 0 ? round(($unjustifiedCount / $totalSessions) * 100, 1) : 0.0;
        $atRisk = ($attendanceRate < 75.0 || $unjustifiedRate >= 15.0);

        return response()->json([
            'student' => [
                'id'          => $student->id,
                'full_name'   => $student->full_name,
                'student_uid' => $student->student_uid,
            ],
            'summary' => [
                'total_sessions'       => $totalSessions,
                'present_count'        => $presentCount,
                'unjustified_count'    => $unjustifiedCount,
                'justified_count'      => $justifiedCount,
                'late_count'           => $lateCount,
                'attendance_rate'      => $attendanceRate,
                'unjustified_rate'     => $unjustifiedRate,
                'at_risk_of_exclusion' => $atRisk,
                'risk_message'         => $atRisk ? 'Atenção: Aluno em risco de exclusão por excesso de faltas injustificadas (< 75% assiduidade).' : null,
            ],
        ]);
    }
}
