<?php

namespace Database\Seeders;

use App\Enums\AttendanceStatusEnum;
use App\Models\AcademicYear;
use App\Models\Attendance;
use App\Models\AttendanceRecord;
use App\Models\ClassGroup;
use App\Models\Enrollment;
use App\Models\Subject;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AttendanceSeeder extends Seeder
{
    public function run(): void
    {
        $academicYear = AcademicYear::where('is_active', true)->first();
        $classGroups = ClassGroup::with('enrollments.student')->get();
        $teacher = User::first();
        $subject = Subject::first();

        if (!$academicYear || $classGroups->isEmpty() || !$teacher) {
            return;
        }

        $topics = [
            'Língua Portuguesa: Estrutura da Frase e Pontuação',
            'Matemática: Operações Fundamentais e Resolução de Problemas',
            'Estudo do Meio: O Clima e as Estações do Ano em Angola',
            'Ciências da Natureza: O Sistema Solar e os Planetas',
            'História de Angola: As Primeiras Sociedades e Reinos Tradicionais',
        ];

        DB::transaction(function () use ($classGroups, $academicYear, $teacher, $subject, $topics) {
            // Criar sessões de chamada para os últimos 5 dias úteis
            for ($dayOffset = 5; $dayOffset >= 1; $dayOffset--) {
                $date = Carbon::now()->subDays($dayOffset)->toDateString();

                foreach ($classGroups as $cgIndex => $classGroup) {
                    $enrollments = $classGroup->enrollments;
                    if ($enrollments->isEmpty()) continue;

                    $topic = $topics[($cgIndex + $dayOffset) % count($topics)];

                    $attendance = Attendance::firstOrCreate(
                        [
                            'class_group_id' => $classGroup->id,
                            'date'           => $date,
                            'lesson_number'  => 1,
                        ],
                        [
                            'subject_id'          => $subject?->id,
                            'academic_year_id'    => $academicYear->id,
                            'topic'               => $topic,
                            'notes'               => 'Aula ministrada com elevada participação dos alunos.',
                            'recorded_by_user_id' => $teacher->id,
                            'synced_at'           => now(),
                        ]
                    );

                    foreach ($enrollments as $idx => $enrollment) {
                        // 90% presentes, 5% falta injustificada, 5% atraso
                        $status = AttendanceStatusEnum::PRESENTE;
                        if ($idx === 1 && $dayOffset === 2) {
                            $status = AttendanceStatusEnum::FALTA_JUSTIFICADA;
                        } elseif ($idx === 2 && $dayOffset === 1) {
                            $status = AttendanceStatusEnum::FALTA_INJUSTIFICADA;
                        } elseif ($idx === 0 && $dayOffset === 3) {
                            $status = AttendanceStatusEnum::ATRASO;
                        }

                        AttendanceRecord::firstOrCreate(
                            [
                                'attendance_id' => $attendance->id,
                                'student_id'    => $enrollment->student_id,
                            ],
                            [
                                'enrollment_id'        => $enrollment->id,
                                'status'               => $status,
                                'justification_reason' => ($status === AttendanceStatusEnum::FALTA_JUSTIFICADA) ? 'Atestado médico do Posto de Saúde' : null,
                                'justified_at'         => ($status === AttendanceStatusEnum::FALTA_JUSTIFICADA) ? now() : null,
                                'justified_by_user_id' => ($status === AttendanceStatusEnum::FALTA_JUSTIFICADA) ? $teacher->id : null,
                            ]
                        );
                    }
                }
            }
        });
    }
}
