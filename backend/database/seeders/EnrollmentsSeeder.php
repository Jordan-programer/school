<?php

namespace Database\Seeders;

use App\Enums\EnrollmentStatusEnum;
use App\Enums\EnrollmentTypeEnum;
use App\Models\AcademicYear;
use App\Models\ClassGroup;
use App\Models\Enrollment;
use App\Models\Grade;
use App\Models\School;
use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EnrollmentsSeeder extends Seeder
{
    public function run(): void
    {
        $school = School::first();
        $academicYear = AcademicYear::where('is_active', true)->first() ?? AcademicYear::first();
        $user = User::first();

        if (!$school || !$academicYear) {
            return;
        }

        $yearNum = (int) substr($academicYear->name, 0, 4);
        if ($yearNum < 2000) {
            $yearNum = now()->year;
        }

        // Mapeamento dos alunos criados
        $students = Student::all();
        $grades = Grade::orderBy('order_index')->get();

        if ($students->isEmpty() || $grades->isEmpty()) {
            return;
        }

        DB::transaction(function () use ($students, $grades, $school, $academicYear, $user, $yearNum) {
            foreach ($students as $index => $student) {
                // Atribuir classe de acordo com o index
                $gradeIndex = $index % $grades->count();
                $grade = $grades[$gradeIndex];

                // Buscar turma compatível
                $classGroup = ClassGroup::where('academic_year_id', $academicYear->id)
                    ->where('grade_id', $grade->id)
                    ->first();

                // Tipo: se index for ímpar, é CONFIRMACAO; se par, NOVA_MATRICULA
                $isRenewal = ($index % 2 === 1);
                $type = $isRenewal ? EnrollmentTypeEnum::CONFIRMACAO : EnrollmentTypeEnum::NOVA_MATRICULA;

                $distortion = Enrollment::checkAgeDistortion($student, $grade, $yearNum);
                $code = Enrollment::generateEnrollmentCode($type, $yearNum);

                $enrollment = Enrollment::firstOrCreate(
                    [
                        'student_id'       => $student->id,
                        'academic_year_id' => $academicYear->id,
                    ],
                    [
                        'grade_id'            => $grade->id,
                        'class_group_id'      => $classGroup?->id,
                        'school_id'           => $school->id,
                        'enrollment_code'     => $code,
                        'enrollment_type'     => $type,
                        'status'              => EnrollmentStatusEnum::CONFIRMADA,
                        'order_number'        => $classGroup ? ($index + 1) : null,
                        'age_at_enrollment'   => $distortion['age'],
                        'has_age_distortion'  => $distortion['has_distortion'],
                        'distortion_details'  => $distortion['details'],
                        'notes'               => 'Matrícula regularizada no início do ano lectivo.',
                        'enrolled_at'         => now()->subDays(rand(1, 30)),
                        'enrolled_by_user_id' => $user?->id,
                    ]
                );
            }
        });
    }
}
