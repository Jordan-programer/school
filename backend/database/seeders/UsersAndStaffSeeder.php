<?php

namespace Database\Seeders;

use App\Enums\RoleEnum;
use App\Models\Staff;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UsersAndStaffSeeder extends Seeder
{
    /**
     * Cadastra utilizadores e membros da equipa escolar com nomes e dados de Angola.
     */
    public function run(): void
    {
        $defaultPassword = Hash::make('Senha12345!');

        $users = [
            [
                'name' => 'Dr. Manuel Domingos António',
                'email' => 'director@sisgestesc.ao',
                'phone' => '+244923112233',
                'role' => RoleEnum::DIRECTOR->value,
                'staff' => [
                    'staff_code' => 'DIR-001',
                    'id_document_type' => 'BI',
                    'id_document_number' => '002938475LA039',
                    'position' => 'Director Geral',
                    'academic_degree' => 'Mestrado em Administração Escolar',
                    'specialty' => 'Gestão e Liderança Pedagógica',
                ],
            ],
            [
                'name' => 'Prof. Sebastião André Caxito',
                'email' => 'pedagogico@sisgestesc.ao',
                'phone' => '+244923445566',
                'role' => RoleEnum::PEDAGOGIC_SUBDIRECTOR->value,
                'staff' => [
                    'staff_code' => 'SUB-PED-001',
                    'id_document_type' => 'BI',
                    'id_document_number' => '004817263LA042',
                    'position' => 'Subdirector Pedagógico',
                    'academic_degree' => 'Licenciatura em Ciências da Educação',
                    'specialty' => 'Coordenação Curricular',
                ],
            ],
            [
                'name' => 'Dra. Esperança Muquixi Damião',
                'email' => 'administrativo@sisgestesc.ao',
                'phone' => '+244923778899',
                'role' => RoleEnum::ADMINISTRATIVE_SUBDIRECTOR->value,
                'staff' => [
                    'staff_code' => 'SUB-ADM-001',
                    'id_document_type' => 'BI',
                    'id_document_number' => '005928371BE045',
                    'position' => 'Subdirectora Administrativa',
                    'academic_degree' => 'Licenciatura em Economia e Gestão',
                    'specialty' => 'Administração Pública',
                ],
            ],
            [
                'name' => 'Maria Teresa Zau',
                'email' => 'secretaria@sisgestesc.ao',
                'phone' => '+244931223344',
                'role' => RoleEnum::SECRETARY->value,
                'staff' => [
                    'staff_code' => 'SEC-001',
                    'id_document_type' => 'BI',
                    'id_document_number' => '006192837CA028',
                    'position' => 'Chefe de Secretaria',
                    'academic_degree' => 'Técnico Médio em Gestão Escolar',
                    'specialty' => 'Processos e Arquivo Escolar',
                ],
            ],
            [
                'name' => 'Kiala Bumba José',
                'email' => 'professor.matematica@sisgestesc.ao',
                'phone' => '+244945667788',
                'role' => RoleEnum::TEACHER->value,
                'staff' => [
                    'staff_code' => 'DOC-001',
                    'id_document_type' => 'BI',
                    'id_document_number' => '007829103LA031',
                    'position' => 'Professor de Matemática',
                    'academic_degree' => 'Licenciatura em Ensino da Matemática',
                    'specialty' => 'Matemática e Física',
                ],
            ],
            [
                'name' => 'Victorino Caxito Nzuzi',
                'email' => 'coordenador@sisgestesc.ao',
                'phone' => '+244912334455',
                'role' => RoleEnum::COORDINATOR->value,
                'staff' => [
                    'staff_code' => 'COORD-001',
                    'id_document_type' => 'BI',
                    'id_document_number' => '008910293UE034',
                    'position' => 'Coordenador de Disciplina',
                    'academic_degree' => 'Licenciatura em Letras / Língua Portuguesa',
                    'specialty' => 'Língua Portuguesa',
                ],
            ],
            [
                'name' => 'Afonso Muxima Cambondo',
                'email' => 'tesoureiro@sisgestesc.ao',
                'phone' => '+244922119988',
                'role' => RoleEnum::TREASURER->value,
                'staff' => [
                    'staff_code' => 'TES-001',
                    'id_document_type' => 'BI',
                    'id_document_number' => '009182736KN040',
                    'position' => 'Tesoureiro',
                    'academic_degree' => 'Contabilidade e Finanças',
                    'specialty' => 'Gestão de Tesouraria e Propinas',
                ],
            ],
            [
                'name' => 'Dr. João Pedro Luanda',
                'email' => 'fiscal.provincial@sisgestesc.ao',
                'phone' => '+244991002211',
                'role' => RoleEnum::PROVINCIAL_INSPECTOR->value,
                'staff' => null, // Fiscal externo
            ],
            [
                'name' => 'Ana Paula Lourenço',
                'email' => 'encarregado.teste@sisgestesc.ao',
                'phone' => '+244933556677',
                'role' => RoleEnum::GUARDIAN->value,
                'staff' => null,
            ],
            [
                'name' => 'Mateus Lourenço Domingos',
                'email' => 'aluno.teste@sisgestesc.ao',
                'phone' => '+244944889900',
                'role' => RoleEnum::STUDENT->value,
                'staff' => null,
            ],
        ];

        foreach ($users as $userData) {
            $user = User::firstOrCreate(
                ['email' => $userData['email']],
                [
                    'name' => $userData['name'],
                    'phone' => $userData['phone'],
                    'password' => $defaultPassword,
                    'is_active' => true,
                ]
            );

            // Atribuir papel Spatie
            $user->syncRoles([$userData['role']]);

            // Criar registro de staff se aplicável
            if (!empty($userData['staff'])) {
                Staff::firstOrCreate(
                    ['staff_code' => $userData['staff']['staff_code']],
                    [
                        'user_id' => $user->id,
                        'full_name' => $userData['name'],
                        'id_document_type' => $userData['staff']['id_document_type'],
                        'id_document_number' => $userData['staff']['id_document_number'],
                        'position' => $userData['staff']['position'],
                        'academic_degree' => $userData['staff']['academic_degree'],
                        'specialty' => $userData['staff']['specialty'],
                        'phone_primary' => $userData['phone'],
                        'email' => $userData['email'],
                        'hire_date' => now()->subYears(2)->toDateString(),
                        'is_active' => true,
                    ]
                );
            }
        }
    }
}
