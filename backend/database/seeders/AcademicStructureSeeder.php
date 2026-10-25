<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use App\Models\ClassGroup;
use App\Models\Classroom;
use App\Models\CurriculumPlan;
use App\Models\Cycle;
use App\Models\Grade;
use App\Models\Level;
use App\Models\School;
use App\Models\Shift;
use App\Models\Staff;
use App\Models\Subject;
use App\Models\TeachingAssignment;
use App\Models\Term;
use Illuminate\Database\Seeder;

class AcademicStructureSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Criar Escola Base de Referência em Luanda
        $school = School::firstOrCreate(
            ['code' => 'ESC-1042-LDA'],
            [
                'name' => 'Complexo Escolar Nº 1042 "Comandante Giel"',
                'tax_id' => '5412983742',
                'contact_email' => 'geral.esc1042@educacao.gov.ao',
                'contact_phone' => '+244923554433',
                'province' => 'Luanda',
                'municipality' => 'Luanda',
                'address' => 'Bairro Maianga, Rua Comandante Giel, Luanda',
                'is_active' => true,
            ]
        );

        // Actualizar funcionários existentes para pertencer a esta escola
        Staff::whereNull('school_id')->update(['school_id' => $school->id]);

        // 2. Criar Ano Lectivo 2026
        $academicYear = AcademicYear::firstOrCreate(
            ['school_id' => $school->id, 'name' => 'Ano Lectivo 2026'],
            [
                'start_date' => '2026-02-02',
                'end_date' => '2026-11-30',
                'is_active' => true,
                'is_closed' => false,
            ]
        );

        // 3. Criar os 3 Trimestres Oficiais com Prazos de Conselho
        $termsData = [
            [
                'term_number' => 1,
                'name' => '1º Trimestre',
                'start_date' => '2026-02-02',
                'end_date' => '2026-05-08',
                'grades_deadline' => '2026-05-15',
            ],
            [
                'term_number' => 2,
                'name' => '2º Trimestre',
                'start_date' => '2026-05-18',
                'end_date' => '2026-08-14',
                'grades_deadline' => '2026-08-21',
            ],
            [
                'term_number' => 3,
                'name' => '3º Trimestre',
                'start_date' => '2026-08-24',
                'end_date' => '2026-11-30',
                'grades_deadline' => '2026-12-05',
            ],
        ];

        foreach ($termsData as $t) {
            Term::firstOrCreate(
                ['academic_year_id' => $academicYear->id, 'term_number' => $t['term_number']],
                [
                    'name' => $t['name'],
                    'start_date' => $t['start_date'],
                    'end_date' => $t['end_date'],
                    'grades_deadline' => $t['grades_deadline'],
                    'is_closed' => false,
                ]
            );
        }

        // 4. Criar Níveis de Ensino Angolanos
        $levelIni = Level::firstOrCreate(['code' => 'INICIACAO'], ['name' => 'Educação Pré-Escolar / Iniciação', 'order_index' => 1]);
        $levelPri = Level::firstOrCreate(['code' => 'PRIMARIO'], ['name' => 'Ensino Primário Geral', 'order_index' => 2]);
        $levelSec = Level::firstOrCreate(['code' => 'PRIMEIRO_CICLO_SECUNDARIO'], ['name' => '1º Ciclo do Ensino Secundário', 'order_index' => 3]);

        // 5. Criar Ciclos Pedagógicos
        $cycleIni = Cycle::firstOrCreate(
            ['level_id' => $levelIni->id, 'cycle_number' => 1],
            ['name' => 'Iniciação', 'order_index' => 1]
        );

        $cyclePri1 = Cycle::firstOrCreate(
            ['level_id' => $levelPri->id, 'cycle_number' => 1],
            ['name' => '1º Ciclo do Primário (1ª e 2ª Classes)', 'order_index' => 2]
        );

        $cyclePri2 = Cycle::firstOrCreate(
            ['level_id' => $levelPri->id, 'cycle_number' => 2],
            ['name' => '2º Ciclo do Primário (3ª e 4ª Classes)', 'order_index' => 3]
        );

        $cyclePri3 = Cycle::firstOrCreate(
            ['level_id' => $levelPri->id, 'cycle_number' => 3],
            ['name' => '3º Ciclo do Primário (5ª e 6ª Classes)', 'order_index' => 4]
        );

        $cycleSec = Cycle::firstOrCreate(
            ['level_id' => $levelSec->id, 'cycle_number' => 1],
            ['name' => '1º Ciclo do Ensino Secundário Geral (7ª a 9ª)', 'order_index' => 5]
        );

        // 6. Criar Classes Escolares (com is_cycle_ending na 2ª, 4ª, 6ª e 9ª classes)
        $gradesData = [
            ['cycle_id' => $cycleIni->id, 'code' => 'INI', 'name' => 'Iniciação', 'order_index' => 0, 'is_cycle_ending' => false],
            ['cycle_id' => $cyclePri1->id, 'code' => '1A', 'name' => '1ª Classe', 'order_index' => 1, 'is_cycle_ending' => false],
            ['cycle_id' => $cyclePri1->id, 'code' => '2A', 'name' => '2ª Classe', 'order_index' => 2, 'is_cycle_ending' => true],
            ['cycle_id' => $cyclePri2->id, 'code' => '3A', 'name' => '3ª Classe', 'order_index' => 3, 'is_cycle_ending' => false],
            ['cycle_id' => $cyclePri2->id, 'code' => '4A', 'name' => '4ª Classe', 'order_index' => 4, 'is_cycle_ending' => true],
            ['cycle_id' => $cyclePri3->id, 'code' => '5A', 'name' => '5ª Classe', 'order_index' => 5, 'is_cycle_ending' => false],
            ['cycle_id' => $cyclePri3->id, 'code' => '6A', 'name' => '6ª Classe', 'order_index' => 6, 'is_cycle_ending' => true],
            ['cycle_id' => $cycleSec->id, 'code' => '7A', 'name' => '7ª Classe', 'order_index' => 7, 'is_cycle_ending' => false],
            ['cycle_id' => $cycleSec->id, 'code' => '8A', 'name' => '8ª Classe', 'order_index' => 8, 'is_cycle_ending' => false],
            ['cycle_id' => $cycleSec->id, 'code' => '9A', 'name' => '9ª Classe', 'order_index' => 9, 'is_cycle_ending' => true],
        ];

        $gradesMap = [];
        foreach ($gradesData as $g) {
            $grade = Grade::firstOrCreate(
                ['code' => $g['code']],
                [
                    'cycle_id' => $g['cycle_id'],
                    'name' => $g['name'],
                    'order_index' => $g['order_index'],
                    'is_cycle_ending' => $g['is_cycle_ending'],
                ]
            );
            $gradesMap[$g['code']] = $grade;
        }

        // 7. Criar Disciplinas do Currículo Angolano
        $subjectsData = [
            ['code' => 'PORT', 'name' => 'Língua Portuguesa', 'level_id' => null],
            ['code' => 'MAT', 'name' => 'Matemática', 'level_id' => null],
            ['code' => 'EST_MEIO', 'name' => 'Estudo do Meio', 'level_id' => $levelPri->id],
            ['code' => 'CNAT', 'name' => 'Ciências da Natureza', 'level_id' => $levelSec->id],
            ['code' => 'HIST', 'name' => 'História', 'level_id' => $levelSec->id],
            ['code' => 'GEO', 'name' => 'Geografia', 'level_id' => $levelSec->id],
            ['code' => 'EMC', 'name' => 'Educação Moral e Cívica', 'level_id' => null],
            ['code' => 'FIS', 'name' => 'Física', 'level_id' => $levelSec->id],
            ['code' => 'QUI', 'name' => 'Química', 'level_id' => $levelSec->id],
            ['code' => 'BIO', 'name' => 'Biologia', 'level_id' => $levelSec->id],
            ['code' => 'ING', 'name' => 'Língua Inglesa', 'level_id' => $levelSec->id],
            ['code' => 'ED_FIS', 'name' => 'Educação Física', 'level_id' => null],
            ['code' => 'EVP', 'name' => 'Educação Visual e Plástica', 'level_id' => null],
        ];

        $subjectsMap = [];
        foreach ($subjectsData as $s) {
            $sub = Subject::firstOrCreate(
                ['code' => $s['code']],
                ['name' => $s['name'], 'level_id' => $s['level_id']]
            );
            $subjectsMap[$s['code']] = $sub;
        }

        // 8. Criar Planos Curriculares para as Classes (ex: 7ª Classe e 1ª Classe)
        $curriculumMap = [
            '1A' => [
                ['sub' => 'PORT', 'hours' => 6, 'core' => true],
                ['sub' => 'MAT', 'hours' => 5, 'core' => true],
                ['sub' => 'EST_MEIO', 'hours' => 4, 'core' => false],
                ['sub' => 'EMC', 'hours' => 2, 'core' => false],
                ['sub' => 'ED_FIS', 'hours' => 2, 'core' => false],
                ['sub' => 'EVP', 'hours' => 2, 'core' => false],
            ],
            '7A' => [
                ['sub' => 'PORT', 'hours' => 5, 'core' => true],
                ['sub' => 'MAT', 'hours' => 5, 'core' => true],
                ['sub' => 'ING', 'hours' => 3, 'core' => false],
                ['sub' => 'HIST', 'hours' => 3, 'core' => false],
                ['sub' => 'GEO', 'hours' => 3, 'core' => false],
                ['sub' => 'FIS', 'hours' => 3, 'core' => false],
                ['sub' => 'QUI', 'hours' => 3, 'core' => false],
                ['sub' => 'BIO', 'hours' => 3, 'core' => false],
                ['sub' => 'EMC', 'hours' => 2, 'core' => false],
                ['sub' => 'ED_FIS', 'hours' => 2, 'core' => false],
            ],
        ];

        foreach ($curriculumMap as $gCode => $plans) {
            $grade = $gradesMap[$gCode] ?? null;
            if (!$grade) continue;

            foreach ($plans as $p) {
                $sub = $subjectsMap[$p['sub']] ?? null;
                if (!$sub) continue;

                CurriculumPlan::firstOrCreate(
                    [
                        'academic_year_id' => $academicYear->id,
                        'grade_id' => $grade->id,
                        'subject_id' => $sub->id,
                    ],
                    [
                        'weekly_hours' => $p['hours'],
                        'is_core' => $p['core'],
                    ]
                );
            }
        }

        // 9. Criar Turnos
        $shiftManha = Shift::firstOrCreate(
            ['school_id' => $school->id, 'name' => 'Manhã'],
            ['start_time' => '07:30:00', 'end_time' => '12:45:00']
        );
        $shiftTarde = Shift::firstOrCreate(
            ['school_id' => $school->id, 'name' => 'Tarde'],
            ['start_time' => '13:00:00', 'end_time' => '18:15:00']
        );
        $shiftNoite = Shift::firstOrCreate(
            ['school_id' => $school->id, 'name' => 'Vespertino / Noite'],
            ['start_time' => '17:30:00', 'end_time' => '21:30:00']
        );

        // 10. Criar Salas de Aula Físicas
        $rooms = [];
        for ($i = 1; $i <= 8; $i++) {
            $roomName = sprintf('Sala %02d', $i);
            $rooms[$i] = Classroom::firstOrCreate(
                ['school_id' => $school->id, 'name' => $roomName],
                ['capacity' => 45, 'location_block' => $i <= 4 ? 'Bloco A' : 'Bloco B']
            );
        }

        // 11. Criar Turmas de Amostra para o Ano Lectivo 2026
        $turmasData = [
            ['name' => 'Iniciação A', 'grade_code' => 'INI', 'shift_id' => $shiftManha->id, 'room_idx' => 1],
            ['name' => '1ª Classe A', 'grade_code' => '1A', 'shift_id' => $shiftManha->id, 'room_idx' => 2],
            ['name' => '2ª Classe A', 'grade_code' => '2A', 'shift_id' => $shiftManha->id, 'room_idx' => 3],
            ['name' => '4ª Classe A', 'grade_code' => '4A', 'shift_id' => $shiftTarde->id, 'room_idx' => 4],
            ['name' => '6ª Classe A', 'grade_code' => '6A', 'shift_id' => $shiftManha->id, 'room_idx' => 5],
            ['name' => '7ª Classe A', 'grade_code' => '7A', 'shift_id' => $shiftManha->id, 'room_idx' => 6],
            ['name' => '7ª Classe B', 'grade_code' => '7A', 'shift_id' => $shiftTarde->id, 'room_idx' => 6],
            ['name' => '8ª Classe A', 'grade_code' => '8A', 'shift_id' => $shiftManha->id, 'room_idx' => 7],
            ['name' => '9ª Classe A', 'grade_code' => '9A', 'shift_id' => $shiftManha->id, 'room_idx' => 8],
        ];

        $turmasMap = [];
        foreach ($turmasData as $td) {
            $grade = $gradesMap[$td['grade_code']] ?? null;
            if (!$grade) continue;

            $turma = ClassGroup::firstOrCreate(
                ['academic_year_id' => $academicYear->id, 'name' => $td['name']],
                [
                    'school_id' => $school->id,
                    'grade_id' => $grade->id,
                    'shift_id' => $td['shift_id'],
                    'classroom_id' => $rooms[$td['room_idx']]->id,
                    'max_students' => 45,
                    'is_active' => true,
                ]
            );
            $turmasMap[$td['name']] = $turma;
        }

        // 12. Atribuir Professor de Matemática (Kiala Bumba José) à 7ª Classe A
        $professorStaff = Staff::where('staff_code', 'DOC-001')->first();
        $turma7A = $turmasMap['7ª Classe A'] ?? null;
        $subMat = $subjectsMap['MAT'] ?? null;

        if ($professorStaff && $turma7A && $subMat) {
            TeachingAssignment::firstOrCreate(
                ['class_group_id' => $turma7A->id, 'subject_id' => $subMat->id],
                [
                    'academic_year_id' => $academicYear->id,
                    'staff_id' => $professorStaff->id,
                ]
            );
        }
    }
}
